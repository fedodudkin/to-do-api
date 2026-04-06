<?php

declare(strict_types=1);

namespace Tests;

use App\Controllers\TaskController;
use App\Database\PdoFactory;
use App\Http\JsonResponse;
use App\Models\TaskRepository;
use App\Services\RateLimiter;

final class SecurityTest extends TestCase
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
     * Тест XSS санитизации при создании задачи
     */
    public function testXssSanitizationInCreate(): void
    {
        $xssPayload = '<script>alert("XSS")</script>';
        $expectedSanitized = '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;';
        
        $taskData = [
            'title' => $xssPayload,
            'body' => $xssPayload,
            'completed' => false,
        ];

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/tasks';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        
        $jsonData = json_encode($taskData, JSON_UNESCAPED_UNICODE);
        $GLOBALS['__PHP_INPUT_MOCK__'] = $jsonData;
        
        MockHelper::installFileGetContentsMock();
        
        ob_start();
        $this->controller->store();
        $output = ob_get_clean();
        
        MockHelper::clearInput();
        
        $response = json_decode($output, true);
        
        // Проверяем, что XSS был санитизирован
        $this->assertTrue($response['success'] ?? false);
        $this->assertEquals(201, $response['data']['status_code'] ?? 500);
        $this->assertEquals($expectedSanitized, $response['data']['title']);
        $this->assertEquals($expectedSanitized, $response['data']['body']);
    }

    /**
     * Тест XSS санитизации при обновлении задачи
     */
    public function testXssSanitizationInUpdate(): void
    {
        // Создаем задачу
        $task = $this->repository->create([
            'title' => 'Original title',
            'body' => 'Original body',
            'completed' => false,
        ]);

        $xssPayload = '<img src="x" onerror="alert(\'XSS\')">';
        $expectedSanitized = '&lt;img src=&quot;x&quot; onerror=&quot;alert(\'XSS\')&quot;&gt;';
        
        $updateData = [
            'title' => $xssPayload,
            'body' => $xssPayload,
        ];

        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['REQUEST_URI'] = '/tasks/' . $task->id;
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        
        $jsonData = json_encode($updateData, JSON_UNESCAPED_UNICODE);
        $GLOBALS['__PHP_INPUT_MOCK__'] = $jsonData;
        
        MockHelper::installFileGetContentsMock();
        
        ob_start();
        $this->controller->update($task->id);
        $output = ob_get_clean();
        
        MockHelper::clearInput();
        
        $response = json_decode($output, true);
        
        // Проверяем, что XSS был санитизирован
        $this->assertTrue($response['success'] ?? false);
        $this->assertEquals(200, $response['data']['status_code'] ?? 500);
        $this->assertEquals($expectedSanitized, $response['data']['title']);
        $this->assertEquals($expectedSanitized, $response['data']['body']);
    }

    /**
     * Тест CSRF защиты - запрос без заголовка должен быть заблокирован
     */
    public function testCsrfProtectionWithoutHeader(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/tasks';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        // Отсутствует заголовок X-Requested-With
        
        $taskData = [
            'title' => 'Test task',
            'body' => 'Test body',
            'completed' => false,
        ];

        $jsonData = json_encode($taskData, JSON_UNESCAPED_UNICODE);
        $GLOBALS['__PHP_INPUT_MOCK__'] = $jsonData;
        
        MockHelper::installFileGetContentsMock();
        
        ob_start();
        // CSRF middleware должен заблокировать запрос
        $this->expectException(\Exception::class);
        $this->controller->store();
        $output = ob_get_clean();
        
        MockHelper::clearInput();
    }

    /**
     * Тест CSRF защиты - запрос с правильным заголовком должен пройти
     */
    public function testCsrfProtectionWithCorrectHeader(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/tasks';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest'; // Правильный заголовок
        
        $taskData = [
            'title' => 'Test task',
            'body' => 'Test body',
            'completed' => false,
        ];

        $jsonData = json_encode($taskData, JSON_UNESCAPED_UNICODE);
        $GLOBALS['__PHP_INPUT_MOCK__'] = $jsonData;
        
        MockHelper::installFileGetContentsMock();
        
        ob_start();
        $this->controller->store();
        $output = ob_get_clean();
        
        MockHelper::clearInput();
        
        $response = json_decode($output, true);
        
        // Запрос должен пройти успешно
        $this->assertTrue($response['success'] ?? false);
        $this->assertEquals(201, $response['data']['status_code'] ?? 500);
    }

    /**
     * Тест GET запросов - CSRF защита не применяется
     */
    public function testGetRequestsBypassCsrfProtection(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/tasks';
        // GET запросы не требуют заголовка X-Requested-With
        
        MockHelper::setInput('');
        MockHelper::installFileGetContentsMock();
        
        ob_start();
        $this->controller->index();
        $output = ob_get_clean();
        
        MockHelper::clearInput();
        
        $response = json_decode($output, true);
        
        // GET запрос должен пройти без CSRF проверки
        $this->assertTrue($response['success'] ?? false);
        $this->assertEquals(200, $response['data']['status_code'] ?? 500);
    }

    /**
     * Тест сложного XSS вектора
     */
    public function testComplexXssVector(): void
    {
        $complexXss = 'javascript:alert(\'XSS\');"><script>alert(\'XSS\')</script><img src=x onerror=alert(\'XSS\')>';
        $expectedSanitized = 'javascript:alert(\'XSS\');&quot;&gt;&lt;script&gt;alert(\'XSS\')&lt;/script&gt;&lt;img src=x onerror=alert(\'XSS\')&gt;';
        
        $taskData = [
            'title' => $complexXss,
            'body' => 'Test body',
            'completed' => false,
        ];

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/tasks';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        
        $jsonData = json_encode($taskData, JSON_UNESCAPED_UNICODE);
        $GLOBALS['__PHP_INPUT_MOCK__'] = $jsonData;
        
        MockHelper::installFileGetContentsMock();
        
        ob_start();
        $this->controller->store();
        $output = ob_get_clean();
        
        MockHelper::clearInput();
        
        $response = json_decode($output, true);
        
        // Проверяем, что сложный XSS вектор был санитизирован
        $this->assertTrue($response['success'] ?? false);
        $this->assertEquals(201, $response['data']['status_code'] ?? 500);
        $this->assertEquals($expectedSanitized, $response['data']['title']);
    }
}
