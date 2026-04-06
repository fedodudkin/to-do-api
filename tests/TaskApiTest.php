<?php

declare(strict_types=1);

namespace Tests;

use App\Controllers\TaskController;
use App\Database\PdoFactory;
use App\Http\JsonResponse;
use App\Models\TaskRepository;
use App\Router;
use App\Services\RateLimiter;

final class TaskApiTest extends TestCase
{
    private TaskController $controller;
    private TaskRepository $repository;
    private Router $router;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = new TaskRepository($this->pdo);
        $rateLimiter = new RateLimiter($this->pdo, 100, 60);
        $this->controller = new TaskController($this->repository);
        $this->router = new Router($this->controller);
        
        // Устанавливаем мок для file_get_contents
        MockHelper::installFileGetContentsMock();
    }

    /**
     * Эмулирует HTTP запрос и захватывает JSON ответ
     */
    private function emulateHttpRequest(callable $handler): array
    {
        // Сохраняем оригинальные суперглобальные переменные
        $originalGet = $_GET;
        $originalPost = $_POST;
        $originalServer = $_SERVER;
        
        try {
            // Перехватываем вывод
            ob_start();
            
            // Выполняем обработчик
            $handler();
            
            // Получаем вывод и заголовки
            $output = ob_get_clean();
            $headers = headers_list();
            
            // Очищаем заголовки для следующего теста
            if (function_exists('headers_remove')) {
                headers_remove();
            }
            
            // Парсим JSON ответ
            $responseData = json_decode($output, true);
            
            return [
                'success' => http_response_code() >= 200 && http_response_code() < 300,
                'status_code' => http_response_code(),
                'data' => $responseData['data'] ?? null,
                'error' => $responseData['error'] ?? null,
                'headers' => $headers,
            ];
            
        } catch (\Throwable $e) {
            // Восстанавливаем состояние в случае ошибки
            $_GET = $originalGet;
            $_POST = $originalPost;
            $_SERVER = $originalServer;
            
            throw $e;
        } finally {
            // Восстанавливаем состояние
            $_GET = $originalGet;
            $_POST = $originalPost;
            $_SERVER = $originalServer;
            MockHelper::clearInput();
        }
    }

    /**
     * Эмулирует POST запрос с JSON телом
     */
    private function emulatePostRequest(string $uri, array $data): array
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest'; // CSRF protection
        
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
        MockHelper::setInput($jsonData);
        
        return $this->emulateHttpRequest(fn() => $this->router->dispatch('POST', $uri));
    }

    /**
     * Эмулирует GET запрос
     */
    private function emulateGetRequest(string $uri, array $params = []): array
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = $uri;
        $_GET = $params;
        
        // Очищаем php://input для GET запроса
        MockHelper::setInput('');
        
        return $this->emulateHttpRequest(fn() => $this->router->dispatch('GET', $uri));
    }

    /**
     * Эмулирует PUT запрос
     */
    private function emulatePutRequest(string $uri, int $id, array $data): array
    {
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest'; // CSRF protection
        
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
        MockHelper::setInput($jsonData);
        
        return $this->emulateHttpRequest(fn() => $this->router->dispatch('PUT', $uri));
    }

    /**
     * Эмулирует DELETE запрос
     */
    private function emulateDeleteRequest(string $uri, int $id): array
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest'; // CSRF protection
        
        // Очищаем php://input для DELETE запроса
        MockHelper::setInput('');
        
        return $this->emulateHttpRequest(fn() => $this->router->dispatch('DELETE', $uri));
    }

    public function testGetTasksReturns200AndTaskList(): void
    {
        // Создаем тестовые данные
        $this->repository->create([
            'title' => 'Тестовая задача 1',
            'body' => 'Описание 1',
            'completed' => false,
        ]);
        
        $this->repository->create([
            'title' => 'Тестовая задача 2',
            'body' => 'Описание 2',
            'completed' => true,
        ]);

        $response = $this->emulateGetRequest('/tasks');

        $this->assertTrue($response['success']);
        $this->assertEquals(200, $response['status_code']);
        $this->assertIsArray($response['data']);
        $this->assertCount(2, $response['data']);
        $this->assertEquals('Тестовая задача 1', $response['data'][0]['title']);
        $this->assertEquals('Тестовая задача 2', $response['data'][1]['title']);
    }

    public function testPostTaskReturns201AndCreatedTask(): void
    {
        $taskData = [
            'title' => 'Новая задача из теста',
            'body' => 'Описание новой задачи',
            'completed' => false,
        ];

        $response = $this->emulatePostRequest('/tasks', $taskData);

        $this->assertTrue($response['success']);
        $this->assertEquals(201, $response['status_code']);
        $this->assertIsArray($response['data']);
        $this->assertEquals('Новая задача из теста', $response['data']['title']);
        $this->assertEquals('Описание новой задачи', $response['data']['body']);
        $this->assertFalse($response['data']['completed']);
        $this->assertArrayHasKey('id', $response['data']);
        $this->assertArrayHasKey('created_at', $response['data']);
        $this->assertArrayHasKey('updated_at', $response['data']);
    }

    public function testPostTaskWithEmptyTitleReturns400(): void
    {
        $taskData = [
            'title' => '',
            'body' => 'Описание задачи с пустым заголовком',
            'completed' => false,
        ];

        $response = $this->emulatePostRequest('/tasks', $taskData);

        $this->assertFalse($response['success']);
        $this->assertEquals(400, $response['status_code']);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Поле "title" обязательно и не должно быть пустым', $response['error']['message']);
        $this->assertEquals('validation_error', $response['error']['code']);
    }

    public function testPostTaskWithInvalidJsonReturns400(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/tasks';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest'; // CSRF protection
        
        // Эмулируем невалидный JSON
        MockHelper::setInput('{"title": "test"'); // отсутствует закрывающая кавычка
        
        try {
            $response = $this->emulateHttpRequest(fn() => $this->router->dispatch('POST', '/tasks'));
            
            $this->assertFalse($response['success']);
            $this->assertEquals(400, $response['status_code']);
            $this->assertArrayHasKey('error', $response);
            $this->assertEquals('Некорректный JSON в теле запроса', $response['error']['message']);
            
        } finally {
            MockHelper::clearInput();
        }
    }

    public function testGetNonExistentTaskReturns404(): void
    {
        $response = $this->emulateGetRequest('/tasks/99999');

        $this->assertFalse($response['success']);
        $this->assertEquals(404, $response['status_code']);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Ресурс не найден', $response['error']['message']);
        $this->assertEquals('not_found', $response['error']['code']);
    }

    public function testPutTaskReturns200AndUpdatedTask(): void
    {
        // Создаем задачу для обновления
        $task = $this->repository->create([
            'title' => 'Оригинальный заголовок',
            'body' => 'Оригинальное описание',
            'completed' => false,
        ]);

        $updateData = [
            'title' => 'Обновленный заголовок',
            'completed' => true,
        ];

        $response = $this->emulatePutRequest('/tasks/' . $task->id, $task->id, $updateData);

        $this->assertTrue($response['success']);
        $this->assertEquals(200, $response['status_code']);
        $this->assertEquals('Обновленный заголовок', $response['data']['title']);
        $this->assertEquals('Оригинальное описание', $response['data']['body']); // не изменилось
        $this->assertTrue($response['data']['completed']);
    }

    public function testDeleteTaskReturns204(): void
    {
        // Создаем задачу для удаления
        $task = $this->repository->create([
            'title' => 'Задача для удаления',
            'body' => 'Эта задача будет удалена',
            'completed' => false,
        ]);

        $response = $this->emulateDeleteRequest('/tasks/' . $task->id, $task->id);

        $this->assertTrue($response['success']);
        $this->assertEquals(204, $response['status_code']);
        $this->assertNull($response['data']); // 204 не должен возвращать тело
        
        // Проверяем, что задача действительно удалена
        $deletedTask = $this->repository->findById($task->id);
        $this->assertNull($deletedTask);
    }

    public function testDeleteNonExistentTaskReturns404(): void
    {
        $response = $this->emulateDeleteRequest('/tasks/99999', 99999);

        $this->assertFalse($response['success']);
        $this->assertEquals(404, $response['status_code']);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Ресурс не найден', $response['error']['message']);
    }

    public function testRateLimitingReturns429(): void
    {
        // Создаем Rate Limiter с малым лимитом для теста
        $rateLimiter = new RateLimiter($this->pdo, 2, 60); // 2 запроса в минуту
        
        // Делаем запросы до превышения лимита
        for ($i = 0; $i < 3; $i++) {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['REQUEST_URI'] = '/tasks';
            $_SERVER['REMOTE_ADDR'] = '192.168.1.100'; // фиксированный IP для теста
            
            $result = $rateLimiter->check('192.168.1.100');
            
            if ($i < 2) {
                $this->assertTrue($result['allowed'], "Request $i should be allowed");
                $this->assertEquals(0, $result['retry_after'], "Request $i should not have retry_after");
            } else {
                $this->assertFalse($result['allowed'], "Request $i should be blocked");
                $this->assertGreaterThan(0, $result['retry_after'], "Request $i should have retry_after");
            }
        }
    }
}
