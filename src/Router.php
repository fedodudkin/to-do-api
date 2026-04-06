<?php

declare(strict_types=1);

namespace App;

use App\Controllers\TaskController;
use App\Http\JsonResponse;
use App\Validators\UrlValidator;

final class Router
{
    public function __construct(
        private readonly TaskController $tasks,
    ) {
    }

    public function dispatch(string $method, string $path): void
    {
        $path = $path !== '' ? $path : '/';
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }
        $path = preg_replace('#/+#', '/', $path) ?? $path;
        $path = rtrim($path, '/') ?: '/';

        if ($path === '/tasks') {
            match ($method) {
                'GET' => $this->tasks->index(),
                'POST' => $this->tasks->store(),
                default => JsonResponse::error([
                    'message' => 'Метод не поддерживается',
                    'code' => 'method_not_allowed',
                ], 405),
            };
            return;
        }

        if (preg_match('#^/tasks/(\d+)$#', $path, $m)) {
            try {
                $id = UrlValidator::validateTaskId($m[1]);
                match ($method) {
                    'GET' => $this->tasks->show($id),
                    'PUT', 'PATCH' => $this->tasks->update($id),
                    'DELETE' => $this->tasks->destroy($id),
                    default => JsonResponse::error([
                        'message' => 'Метод не поддерживается',
                        'code' => 'method_not_allowed',
                    ], 405),
                };
                return;
            } catch (\InvalidArgumentException $e) {
                JsonResponse::error([
                    'message' => 'Некорректный ID задачи',
                    'code' => 'invalid_id',
                ], 400);
            }
        }

        JsonResponse::notFound('Маршрут не найден');
    }
}
