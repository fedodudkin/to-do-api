<?php

declare(strict_types=1);

namespace Tests;

use App\Models\TaskRepository;

final class FunctionalTaskApiTest extends TestCase
{
    private TaskRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TaskRepository($this->pdo);
    }

    public function testCreateAndRetrieveTask(): void
    {
        // Создаем задачу через репозиторий
        $task = $this->repository->create([
            'title' => 'Тестовая задача',
            'body' => 'Описание тестовой задачи',
            'completed' => false
        ]);

        // Проверяем, что задача создана
        $this->assertNotNull($task);
        $this->assertGreaterThan(0, $task->id);
        $this->assertEquals('Тестовая задача', $task->title);
        $this->assertEquals('Описание тестовой задачи', $task->body);
        $this->assertFalse($task->completed);

        // Получаем задачу по ID
        $retrievedTask = $this->repository->findById($task->id);
        
        // Проверяем, что задача найдена и данные совпадают
        $this->assertNotNull($retrievedTask);
        $this->assertEquals($task->id, $retrievedTask->id);
        $this->assertEquals($task->title, $retrievedTask->title);
        $this->assertEquals($task->body, $retrievedTask->body);
        $this->assertEquals($task->completed, $retrievedTask->completed);
    }

    public function testCreateTaskWithEmptyTitle(): void
    {
        // Проверяем, что создание задачи с пустым заголовком создает задачу
        // (валидация происходит на уровне контроллера, а не репозитория)
        $task = $this->repository->create([
            'title' => '',
            'body' => 'Описание задачи',
            'completed' => false
        ]);

        // Репозиторий сохраняет даже с пустым заголовком
        $this->assertNotNull($task);
        $this->assertEquals('', $task->title);
    }

    public function testDeleteTask(): void
    {
        // Создаем задачу для удаления
        $task = $this->repository->create([
            'title' => 'Задача для удаления',
            'body' => 'Эта задача будет удалена',
            'completed' => false
        ]);

        $taskId = $task->id;
        
        // Удаляем задачу
        $result = $this->repository->delete($taskId);
        
        // Проверяем, что удаление прошло успешно
        $this->assertTrue($result);
        
        // Проверяем, что задача действительно удалена
        $deletedTask = $this->repository->findById($taskId);
        $this->assertNull($deletedTask);
    }

    public function testGetNonExistentTask(): void
    {
        // Пытаемся получить несуществующую задачу
        $task = $this->repository->findById(99999);
        
        // Проверяем, что задача не найдена
        $this->assertNull($task);
    }

    public function testUpdateTask(): void
    {
        // Создаем задачу для обновления
        $task = $this->repository->create([
            'title' => 'Исходный заголовок',
            'body' => 'Исходное описание',
            'completed' => false
        ]);

        // Обновляем задачу
        $updatedTask = $this->repository->update($task->id, [
            'title' => 'Обновленный заголовок',
            'completed' => true
        ]);

        // Проверяем, что задача обновлена
        $this->assertNotNull($updatedTask);
        $this->assertEquals('Обновленный заголовок', $updatedTask->title);
        $this->assertEquals('Исходное описание', $updatedTask->body); // не изменилось
        $this->assertTrue($updatedTask->completed);
        
        // Проверяем через findById
        $retrievedTask = $this->repository->findById($task->id);
        $this->assertEquals('Обновленный заголовок', $retrievedTask->title);
        $this->assertTrue($retrievedTask->completed);
    }

    public function testUpdateNonExistentTask(): void
    {
        // Пытаемся обновить несуществующую задачу
        $result = $this->repository->update(99999, [
            'title' => 'Новый заголовок'
        ]);
        
        // Проверяем, что обновление не удалось
        $this->assertNull($result);
    }

    public function testListAllTasks(): void
    {
        // Создаем несколько задач
        $task1 = $this->repository->create([
            'title' => 'Задача 1',
            'body' => 'Описание 1',
            'completed' => false
        ]);
        
        $task2 = $this->repository->create([
            'title' => 'Задача 2',
            'body' => 'Описание 2',
            'completed' => true
        ]);

        // Получаем список всех задач
        $allTasks = $this->repository->findAll();
        
        // Проверяем, что в списке есть как минимум 2 задачи
        $this->assertGreaterThanOrEqual(2, count($allTasks));
        
        // Проверяем, что наши задачи есть в списке
        $taskIds = array_map(fn($task) => $task->id, $allTasks);
        $this->assertContains($task1->id, $taskIds);
        $this->assertContains($task2->id, $taskIds);
    }
}
