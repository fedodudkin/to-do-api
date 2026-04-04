<?php

declare(strict_types=1);

namespace Tests;

use App\Database\PdoFactory;
use PDO;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected ?PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем тестовую базу данных в памяти
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Создаем таблицы для тестов
        $this->createTestTables();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Закрываем соединение с базой данных
        $this->pdo = null;
    }

    private function createTestTables(): void
    {
        // Создаем таблицу tasks
        $this->pdo->exec(
            'CREATE TABLE tasks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                body TEXT,
                completed INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )'
        );

        // Создаем таблицу rate_limit
        $this->pdo->exec(
            'CREATE TABLE rate_limit (
                ip TEXT NOT NULL,
                window_start INTEGER NOT NULL,
                count INTEGER NOT NULL DEFAULT 1,
                PRIMARY KEY (ip, window_start)
            )'
        );
    }

    protected function createPdoFactory(): PdoFactory
    {
        return new class($this->pdo) extends PdoFactory {
            public function __construct(private PDO $pdo) {}
            
            public function create(array $config): PDO
            {
                return $this->pdo;
            }
        };
    }
}
