<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\JsonResponse;

final class CsrfProtectionMiddleware
{
    /**
     * Проверяет наличие заголовка X-Requested-With для защиты от CSRF
     */
    public function handle(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // GET запросы не требуют защиты
        if ($method === 'GET') {
            return;
        }
        
        // Проверяем заголовок X-Requested-With
        $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        
        if ($requestedWith !== 'XMLHttpRequest') {
            JsonResponse::error([
                'message' => 'Отсутствует заголовок X-Requested-With',
                'code' => 'csrf_protection',
            ], 403);
        }
    }
}
