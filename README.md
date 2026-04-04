# To-Do REST API 

REST API для управления задачами на PHP 8.2 с SQLite.

## Конфигурация

### Файл .env

Перед запуском создайте файл `.env` на основе `.env.example`:

```bash
cp .env.example .env
```

Основные переменные окружения:

- `DATABASE_PATH` - путь к файлу базы данных SQLite
- `LOG_PATH` - путь к файлу логов
- `RATE_LIMIT_MAX` - максимальное количество запросов в окне
- `RATE_LIMIT_WINDOW_SECONDS` - размер окна rate limiting в секундах
- `APP_ENV` - окружение (local, production)
- `APP_DEBUG` - режим отладки

## Запуск проекта

### С помощью Docker (рекомендуется)

1. Убедитесь, что у вас установлен Docker.
2. Склонируйте репозиторий и запустите контейнеры:
   ```bash
   docker-compose up -d --build
   ```
3. Установите зависимости и инициализируйте БД внутри контейнера:
   ```bash
   docker-compose exec app composer install
   docker-compose exec app php bin/setup-db.php
   ```

API будет доступен по адресу: http://localhost:8080

## Тестирование

### Запуск тестов через Docker

```bash
docker-compose exec app ./vendor/bin/phpunit tests
```

### Запуск тестов локально (требуется PHP 8.2 и sqlite3)

Для запуска тестов выполните:

```bash
# Запуск всех тестов
composer install
./vendor/bin/phpunit tests

# Запуск с покрытием кода
./vendor/bin/phpunit --coverage-html coverage

# Запуск конкретного теста
./vendor/bin/phpunit tests/FunctionalTaskApiTest.php
```

Тесты используют отдельную базу данных SQLite в памяти, поэтому основные данные не затрагиваются.

## Документация API

### Swagger/OpenAPI

Документация API доступна в файле `openapi.yaml`. Вы можете использовать любые инструменты для просмотра OpenAPI спецификаций:

- [Swagger Editor](https://editor.swagger.io/) - загрузите файл `openapi.yaml`
- [Swagger UI](https://swagger.io/tools/swagger-ui/) - для интерактивной документации

### Основные эндпоинты

- `GET /tasks` - получить список всех задач
- `POST /tasks` - создать новую задачу
- `GET /tasks/{id}` - получить задачу по ID
- `PUT /tasks/{id}` - обновить задачу
- `DELETE /tasks/{id}` - удалить задачу

## Структура проекта

- `src/` - исходный код приложения
- `tests/` - тесты
- `config/` - конфигурационные файлы
- `database/` - файлы базы данных SQLite
- `docker/` - конфигурации Docker
- `openapi.yaml` - спецификация API

## Требования

- PHP 8.2+
- SQLite
- Composer
- Docker (опционально)