<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\JsonResponse;
use App\Services\RateLimiter;

final class RateLimitMiddleware
{
    public function __construct(
        private readonly RateLimiter $rateLimiter,
    ) {
    }

    public function handle(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $result = $this->rateLimiter->check($ip);
        if (!$result['allowed']) {
            JsonResponse::tooManyRequests('Слишком много запросов', $result['retry_after']);
        }
    }
}
