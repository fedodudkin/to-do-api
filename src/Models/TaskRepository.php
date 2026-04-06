<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class TaskRepository
{
    public function __construct(
        private readonly PDO $pdo,
    ) {
    }

    /**
     * @return list<Task> Возвращает список всех задач
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, title, body, completed, created_at, updated_at FROM tasks ORDER BY id ASC'
        );
        $rows = $stmt->fetchAll();
        $out = [];
        foreach ($rows as $row) {
            $out[] = Task::fromRow($row);
        }
        return $out;
    }

    public function findById(int $id): ?Task
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, title, body, completed, created_at, updated_at FROM tasks WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }
        return Task::fromRow($row);
    }

    /**
     * @param array{title: string, body: ?string, completed: bool} $data Данные для создания задачи
     */
    public function create(array $data): Task
    {
        $now = $this->now();
        $stmt = $this->pdo->prepare(
            'INSERT INTO tasks (title, body, completed, created_at, updated_at)
             VALUES (:title, :body, :completed, :created_at, :updated_at)'
        );
        $stmt->execute([
            'title' => $this->sanitizeString($data['title']),
            'body' => $data['body'] !== null ? $this->sanitizeString($data['body']) : null,
            'completed' => $data['completed'] ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $id = (int) $this->pdo->lastInsertId();
        $task = $this->findById($id);
        if ($task === null) {
            throw new \RuntimeException('Failed to load created task');
        }
        return $task;
    }

    /**
     * @param array{title?: string, body?: string|null, completed?: bool} $data Данные для обновления задачи
     */
    public function update(int $id, array $data): ?Task
    {
        $existing = $this->findById($id);
        if ($existing === null) {
            return null;
        }
        $title = array_key_exists('title', $data) ? $this->sanitizeString((string) $data['title']) : $existing->title;
        $body = array_key_exists('body', $data) 
            ? ($data['body'] === null ? null : $this->sanitizeString($data['body']))
            : $existing->body;
        $completed = array_key_exists('completed', $data) ? (bool) $data['completed'] : $existing->completed;
        $now = $this->now();
        $stmt = $this->pdo->prepare(
            'UPDATE tasks SET title = :title, body = :body, completed = :completed, updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'title' => $title,
            'body' => $body,
            'completed' => $completed ? 1 : 0,
            'updated_at' => $now,
            'id' => $id,
        ]);
        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM tasks WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    private function now(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }

    /**
     * Санитизация строки для защиты от XSS
     */
    private function sanitizeString(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
