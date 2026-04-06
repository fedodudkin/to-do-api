<?php

declare(strict_types=1);

use App\Controllers\TaskController;
use App\Database\PdoFactory;
use App\Http\JsonResponse;
use App\Logging\LoggerFactory;
use App\Middleware\CsrfProtectionMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\RequestLogMiddleware;
use App\Models\TaskRepository;
use App\Router;
use App\Services\RateLimiter;
use App\Support\Env;

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

Env::load($root . '/.env');

$logPath = Env::string('LOG_PATH', $root . '/logs/app.log');
$logger = LoggerFactory::create($logPath);

/** @var array{dsn: string} $dbConfig */
$dbConfig = require $root . '/config/database.php';

try {
    $pdo = PdoFactory::create($dbConfig);

    $requestLog = new RequestLogMiddleware($logger);
    $requestLog->logIncoming();

    $maxRequests = Env::int('RATE_LIMIT_MAX', 100);
    $windowSeconds = Env::int('RATE_LIMIT_WINDOW_SECONDS', 60);
    $rateLimiter = new RateLimiter($pdo, $maxRequests, $windowSeconds);
    (new RateLimitMiddleware($rateLimiter))->handle();

    // Защита от CSRF для всех не-GET запросов
    (new CsrfProtectionMiddleware())->handle();

    $repo = new TaskRepository($pdo);
    $controller = new TaskController($repo);
    $router = new Router($controller);

    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        $path = '/';
    }
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $router->dispatch($method, $path);
} catch (Throwable $e) {
    $logger->error($e->getMessage(), [
        'exception' => $e,
        'trace' => $e->getTraceAsString(),
    ]);
    JsonResponse::serverError();
}
