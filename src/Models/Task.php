<?php

declare(strict_types=1);

namespace App\Models;

final readonly class Task
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $body,
        public bool $completed,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    /**
     * @param array<string, mixed> $row Строка из базы данных
     */
    public static function fromRow(array $row): self
    {
        $bodyRaw = $row['body'] ?? null;
        return new self(
            id: (int) $row['id'],
            title: (string) $row['title'],
            body: $bodyRaw !== null ? (string) $bodyRaw : null,
            completed: (bool) ((int) ($row['completed'] ?? 0)),
            createdAt: (string) $row['created_at'],
            updatedAt: (string) $row['updated_at'],
        );
    }

    /**
     * @return array<string, mixed> Возвращает массив представление задачи
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'completed' => $this->completed,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
