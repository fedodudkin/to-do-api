<?php

declare(strict_types=1);

use App\Database\PdoFactory;
use App\Support\Env;

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

Env::load($root . '/.env');

/** @var array{dsn: string} $dbConfig */
$dbConfig = require $root . '/config/database.php';
$pdo = PdoFactory::create($dbConfig);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        body TEXT,
        completed INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL
    )'
);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS rate_limit (
        ip TEXT NOT NULL,
        window_start INTEGER NOT NULL,
        count INTEGER NOT NULL DEFAULT 1,
        PRIMARY KEY (ip, window_start)
    )'
);

fwrite(STDOUT, "Database ready.\n");
