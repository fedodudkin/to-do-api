<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Log\LoggerInterface;

final class RequestLogMiddleware
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function logIncoming(): void
    {
        $this->logger->info('Incoming request', [
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
    }
}
