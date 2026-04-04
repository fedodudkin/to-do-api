<?php

declare(strict_types=1);

use App\Support\Env;

$root = dirname(__DIR__);
$path = Env::string('DATABASE_PATH', $root . '/database/app.sqlite');
$path = str_replace('\\', '/', $path);
if ($path !== '' && $path[0] !== '/' && !preg_match('#^[A-Za-z]:/#', $path)) {
    $path = $root . '/' . ltrim($path, '/');
}

return [
    'dsn' => 'sqlite:' . $path,
];
