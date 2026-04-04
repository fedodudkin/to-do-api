<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

final class PdoFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public static function create(array $config): PDO
    {
        $dsn = (string) $config['dsn'];
        $pdo = new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    }
}
