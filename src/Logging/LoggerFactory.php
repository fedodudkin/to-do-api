<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

final class LoggerFactory
{
    public static function create(string $logPath): LoggerInterface
    {
        $dir = dirname($logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler($logPath, Level::Debug));
        return $logger;
    }
}
