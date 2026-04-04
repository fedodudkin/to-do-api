<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class RateLimiter
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly int $maxRequests,
        private readonly int $windowSeconds,
    ) {
    }

    /**
     * @return array{allowed: bool, retry_after: int} Возвращает информацию о разрешении запроса
     */
    public function check(string $ip): array
    {
        $now = time();
        $windowStart = (int) (floor($now / $this->windowSeconds) * $this->windowSeconds);
        
        // Используем RETURNING для атомарного получения актуального значения
        $stmt = $this->pdo->prepare(
            'INSERT INTO rate_limit (ip, window_start, count) VALUES (:ip, :ws, 1)
             ON CONFLICT(ip, window_start) DO UPDATE SET count = count + 1
             RETURNING count'
        );
        $stmt->execute(['ip' => $ip, 'ws' => $windowStart]);
        $count = (int) $stmt->fetchColumn();
        
        if ($count > $this->maxRequests) {
            $retryAfter = $windowStart + $this->windowSeconds - $now;
            if ($retryAfter < 1) {
                $retryAfter = 1;
            }
            return ['allowed' => false, 'retry_after' => $retryAfter];
        }
        return ['allowed' => true, 'retry_after' => 0];
    }
}
