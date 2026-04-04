<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\JsonResponse;
use App\Models\TaskRepository;

final class TaskController
{
    public function __construct(
        private readonly TaskRepository $tasks,
    ) {
    }

    public function index(): never
    {
        $list = $this->tasks->findAll();
        $data = array_map(static fn ($t) => $t->toArray(), $list);
        JsonResponse::success($data, 200);
    }

    public function show(int $id): never
    {
        $task = $this->tasks->findById($id);
        if ($task === null) {
            JsonResponse::notFound();
        }
        JsonResponse::success($task->toArray(), 200);
    }

    public function store(): never
    {
        $body = $this->parseJsonObject();
        $title = isset($body['title']) ? trim((string) $body['title']) : '';
        if ($title === '') {
            JsonResponse::validationError('Поле "title" обязательно и не должно быть пустым');
        }
        $bodyText = array_key_exists('body', $body)
            ? ($body['body'] === null ? null : (string) $body['body'])
            : null;
        $completed = isset($body['completed']) ? (bool) $body['completed'] : false;
        $task = $this->tasks->create([
            'title' => $title,
            'body' => $bodyText,
            'completed' => $completed,
        ]);
        JsonResponse::success($task->toArray(), 201);
    }

    public function update(int $id): never
    {
        $body = $this->parseJsonObject();
        $patch = [];
        if (array_key_exists('title', $body)) {
            $t = trim((string) $body['title']);
            if ($t === '') {
                JsonResponse::validationError('Поле "title" не может быть пустым');
            }
            $patch['title'] = $t;
        }
        if (array_key_exists('body', $body)) {
            $patch['body'] = $body['body'] === null ? null : (string) $body['body'];
        }
        if (array_key_exists('completed', $body)) {
            $patch['completed'] = (bool) $body['completed'];
        }
        if ($patch === []) {
            JsonResponse::validationError('Нет полей для обновления');
        }
        $updated = $this->tasks->update($id, $patch);
        if ($updated === null) {
            JsonResponse::notFound();
        }
        JsonResponse::success($updated->toArray(), 200);
    }

    public function destroy(int $id): never
    {
        $ok = $this->tasks->delete($id);
        if (!$ok) {
            JsonResponse::notFound();
        }
        JsonResponse::noContent();
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsonObject(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            JsonResponse::validationError('Тело запроса обязательно');
        }
        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            JsonResponse::validationError('Некорректный JSON в теле запроса');
        }
        if (!is_array($data)) {
            JsonResponse::validationError('JSON в теле запроса должен быть объектом');
        }
        /** @var array<string, mixed> $data */
        return $data;
    }
}
