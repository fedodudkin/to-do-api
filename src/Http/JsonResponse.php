<?php

declare(strict_types=1);

namespace App\Http;

final class JsonResponse
{
    /**
     * @param array<string, mixed>|list<mixed>|null $data Данные для ответа
     */
    public static function success(array|null $data, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        $payload = ['data' => $data];
        echo json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function noContent(): never
    {
        http_response_code(204);
        exit;
    }

    /**
     * @param array<string, mixed> $error Данные об ошибке
     */
    public static function error(array $error, int $statusCode): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        $payload = ['error' => $error];
        echo json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function validationError(string $message, string $code = 'validation_error'): never
    {
        self::error([
            'message' => $message,
            'code' => $code,
        ], 400);
    }

    public static function notFound(string $message = 'Ресурс не найден', string $code = 'not_found'): never
    {
        self::error([
            'message' => $message,
            'code' => $code,
        ], 404);
    }

    public static function tooManyRequests(string $message, int $retryAfterSeconds): never
    {
        header('Retry-After: ' . (string) $retryAfterSeconds);
        self::error([
            'message' => $message,
            'code' => 'rate_limit_exceeded',
        ], 429);
    }

    public static function serverError(string $message = 'Внутренняя ошибка сервера', string $code = 'server_error'): never
    {
        self::error([
            'message' => $message,
            'code' => $code,
        ], 500);
    }
}
