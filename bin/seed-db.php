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

// Очищаем таблицу перед наполнением
$pdo->exec('DELETE FROM tasks');

// Реалистичные данные для сидера
$tasks = [
    [
        'title' => 'Купить продукты в магазине',
        'body' => 'Молоко, хлеб, яйца, сыр, куриная грудка',
        'completed' => false,
    ],
    [
        'title' => 'Подготовить презентацию для проекта',
        'body' => 'Слайды: введение, архитектура, демо, заключение. Подготовить примеры кода',
        'completed' => true,
    ],
    [
        'title' => 'Записаться к врачу',
        'body' => 'Терапевт, на следующей неделе в понедельник в 14:00',
        'completed' => false,
    ],
    [
        'title' => 'Починить кран на кухне',
        'body' => 'Купить новый картридж и инструменты',
        'completed' => false,
    ],
    [
        'title' => 'Позвонить родителям',
        'body' => 'Обсудить планы на выходные',
        'completed' => true,
    ],
    [
        'title' => 'Изучить новую технологию',
        'body' => 'Vue.js 3 + Composition API - пройти официальный туториал',
        'completed' => false,
    ],
    [
        'title' => 'Закончить читать книгу "Чистый код"',
        'body' => 'Осталось 3 главы, сделать заметки',
        'completed' => false,
    ],
    [
        'title' => 'Оплатить счета за интернет',
        'body' => 'Ростелеком до 25 числа',
        'completed' => true,
    ],
    [
        'title' => 'Сделать backup проекта',
        'body' => 'Создать архив на Google Drive и внешний HDD',
        'completed' => false,
    ],
    [
        'title' => 'Запланировать отпуск',
        'body' => 'Июль, 2 недели, выбрать направление и забронировать отель',
        'completed' => false,
    ],
    [
        'title' => 'Настроить CI/CD для проекта',
        'body' => 'GitHub Actions: тесты, линтер, деплой на staging',
        'completed' => false,
    ],
    [
        'title' => 'Посетить фитнес-клуб',
        'body' => '3 раза в неделю: понедельник, среда, пятница',
        'completed' => true,
    ],
    [
        'title' => 'Обновить резюме',
        'body' => 'Добавить последние проекты и навыки',
        'completed' => false,
    ],
    [
        'title' => 'Приготовить ужин для друзей',
        'body' => 'Салат, гриль, овощи, напитки. Купить продукты завтра',
        'completed' => false,
    ],
    [
        'title' => 'Проверить настройки почты',
        'body' => 'SPF, DKIM, DMARC записи для домена',
        'completed' => true,
    ],
];

// Вставляем данные
$stmt = $pdo->prepare(
    'INSERT INTO tasks (title, body, completed, created_at, updated_at)
     VALUES (:title, :body, :completed, :created_at, :updated_at)'
);

$now = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');

foreach ($tasks as $task) {
    $stmt->execute([
        'title' => $task['title'],
        'body' => $task['body'],
        'completed' => $task['completed'] ? 1 : 0,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

fwrite(STDOUT, "Database seeded with " . count($tasks) . " tasks.\n");
