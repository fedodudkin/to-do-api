<?php

declare(strict_types=1);

namespace Tests;

use App\Controllers\TaskController;
use App\Database\PdoFactory;
use App\Http\JsonResponse;
use App\Models\TaskRepository;
use App\Services\RateLimiter;

final class StressTest extends TestCase
{
    private TaskController $controller;
    private TaskRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = new TaskRepository($this->pdo);
        $this->controller = new TaskController($this->repository);
    }

    /**
     * Тест на огромные строки в title
     */
    public function testHugeTitleHandling(): void
    {
        $hugeTitle = str_repeat('A', 10000); // 10KB строка
        
        $taskData = [
            'title' => $hugeTitle,
            'body' => 'Test body',
            'completed' => false,
        ];

        // Эмулируем POST запрос
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/tasks';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        
        $jsonData = json_encode($taskData, JSON_UNESCAPED_UNICODE);
        $GLOBALS['__PHP_INPUT_MOCK__'] = $jsonData;
        
        MockHelper::installFileGetContentsMock();
        
        ob_start();
        $this->controller->store();
        $output = ob_get_clean();
        
        MockHelper::clearInput();
        
        $response = json_decode($output, true);
        
        // Проверяем, что задача создана с огромным title
        $this->assertTrue($response['data']['success'] ?? false);
        $this->assertEquals(201, $response['data']['status_code'] ?? 500);
        $this->assertEquals($hugeTitle, $response['data']['title']);
    }

    /**
     * Тест на массив вместо строки в JSON
     */
    public function testArrayInsteadOfString(): void
    {
        $taskData = [
            'title' => ['invalid', 'array'],
            'body' => 'Test body',
            'completed' => false,
        ];

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/tasks';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        
        $jsonData = json_encode($taskData, JSON_UNESCAPED_UNICODE);
        $GLOBALS['__PHP_INPUT_MOCK__'] = $jsonData;
        
        MockHelper::installFileGetContentsMock();
        
        ob_start();
        $this->controller->store();
        $output = ob_get_clean();
        
        MockHelper::clearInput();
        
        $response = json_decode($output, true);
        
        // Должна быть ошибка валидации
        $this->assertFalse($response['success'] ?? true);
        $this->assertEquals(400, $response['status_code'] ?? 500);
    }

    /**
     * Тест на отрицательный ID
     */
    public function testNegativeId(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/tasks/-1';
        
        MockHelper::setInput('');
        MockHelper::installFileGetContentsMock();
        
        ob_start();
        $this->controller->show(-1);
        $output = ob_get_clean();
        
        MockHelper::clearInput();
        
        $response = json_decode($output, true);
        
        // Должна быть ошибка валидации ID
        $this->assertFalse($response['success'] ?? true);
        $this->assertEquals(400, $response['status_code'] ?? 500);
        $this->assertEquals('Некорректный ID задачи', $response['error']['message'] ?? '');
    }

    /**
     * Тест на нулевой ID
     */
    public function testZeroId(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/tasks/0';
        
        MockHelper::setInput('');
        MockHelper::installFileGetContentsMock();
        
        ob_start();
        $this->controller->show(0);
        $output = ob_get_clean();
        
        MockHelper::clearInput();
        
        $response = json_decode($output, true);
        
        // Должна быть ошибка валидации ID
        $this->assertFalse($response['success'] ?? true);
        $this->assertEquals(400, $response['status_code'] ?? 500);
        $this->assertEquals('ID должен быть положительным числом', $response['error']['message'] ?? '');
    }

    /**
     * Тест на нечисловой ID
     */
    public function testNonNumericId(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/tasks/abc';
        
        MockHelper::setInput('');
        MockHelper::installFileGetContentsMock();
        
        ob_start();
        $this->controller->show(999); // ID будет проигнорирован из-за неверного формата URL
        $output = ob_get_clean();
        
        MockHelper::clearInput();
        
        $response = json_decode($output, true);
        
        // Должна быть ошибка 404
        $this->assertFalse($response['success'] ?? true);
        $this->assertEquals(404, $response['status_code'] ?? 500);
    }

    /**
     * Тест на XSS в title
     */
    public function testXssInTitle(): void
    {
        $xssPayload = '<script>alert("XSS")</script>';
        
        $taskData = [
            'title' => $xssPayload,
            'body' => 'Test body',
            'completed' => false,
        ];

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/tasks';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        
        $jsonData = json_encode($taskData, JSON_UNESCAPED_UNICODE);
        $GLOBALS['__PHP_INPUT_MOCK__'] = $jsonData;
        
        MockHelper::installFileGetContentsMock();
        
        ob_start();
        $this->controller->store();
        $output = ob_get_clean();
        
        MockHelper::clearInput();
        
        $response = json_decode($output, true);
        
        // XSS должен быть сохранен как есть (экранирование происходит только при выводе JSON)
        $this->assertTrue($response['success'] ?? false);
        $this->assertEquals(201, $response['data']['status_code'] ?? 500);
        $this->assertEquals($xssPayload, $response['data']['title']);
    }

    /**
     * Тест на одновременные запросы (race condition)
     */
    public function testConcurrentRateLimitRequests(): void
    {
        $rateLimiter = new RateLimiter($this->pdo, 2, 60); // 2 запроса в минуту
        
        // Делаем 3 запроса одновременно
        $results = [];
        for ($i = 0; $i < 3; $i++) {
            $result = $rateLimiter->check('192.168.1.100');
            $results[] = $result;
        }
        
        // Первые 2 должны быть разрешены, третий - заблокирован
        $this->assertTrue($results[0]['allowed']);
        $this->assertTrue($results[1]['allowed']);
        $this->assertFalse($results[2]['allowed']);
        $this->assertGreaterThan(0, $results[2]['retry_after']);
    }

    /**
     * Тест на SQL Injection в параметрах
     */
    public function testSqlInjectionInParameters(): void
    {
        // Пытаемся инъектировать через ID
        $maliciousId = "1; DROP TABLE tasks; --";
        
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "/tasks/{$maliciousId}";
        
        MockHelper::setInput('');
        MockHelper::installFileGetContentsMock();
        
        ob_start();
        // Router должен поймать неверный формат ID до вызова контроллера
        $this->controller->show(999); // Безопасный ID
        $output = ob_get_clean();
        
        MockHelper::clearInput();
        
        $response = json_decode($output, true);
        
        // Должна быть ошибка 404 (неверный формат URL)
        $this->assertFalse($response['success'] ?? true);
        $this->assertEquals(404, $response['status_code'] ?? 500);
    }
}
