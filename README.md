# 🛡️ To-Do REST API | Security-First PHP 8.2

Enterprise-grade REST API для управления задачами с фокусом на безопасность. Построен на PHP 8.2 с использованием современных практик защиты и архитектурных паттернов.

## 🚀 Быстрый запуск

### Docker (рекомендуется)

```bash
# Запуск всего стека (автоматическая установка зависимостей, миграций и БД)
docker-compose up -d --build

# Заполнение БД тестовыми данными (опционально)
docker-compose exec app php bin/seed-db.php
```

API будет доступен по адресу: **http://localhost:8080**

> 💡 **Автоматизация**: Entrypoint контейнера автоматически устанавливает Composer зависимости, создает структуру БД и применяет миграции при первом запуске.

## 🔒 Security Features

### 🛡️ CSRF Protection
Все POST/PUT/DELETE запросы требуют заголовок `X-Requested-With: XMLHttpRequest` для защиты от CSRF-атак.

```bash
curl -X POST http://localhost:8080/tasks \
  -H "Content-Type: application/json" \
  -H "X-Requested-With: XMLHttpRequest" \
  -d '{"title":"API Test Fixed","completed":false}'
```

### 🚫 XSS Prevention
Санитизация всех пользовательских данных через `htmlspecialchars()` на уровне репозитория перед сохранением в БД.

- **Title & Body**: `htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8')`
- **Zero XSS**: Вредоносные скрипты преобразуются в безопасный HTML

### 💉 SQL Injection Protection
- **100% Prepared Statements**: Все SQL-запросы используют параметризацию
- **Zero Concatenation**: Никаких прямых вставок переменных в SQL
- **Type Safety**: Строгая типизация параметров

### ⚡ Rate Limiting
Атомарная защита от DoS-атак на базе SQLite с использованием `INSERT ... RETURNING count`.

- **Default**: 100 запросов в минуту на IP
- **Atomic Operations**: Без race conditions при высокой нагрузке

### 🔍 ID Validation
Строгая валидация ID в URL для защиты от Path Traversal и инъекций.

- **Format Check**: `ctype_digit()` validation
- **Range Protection**: Только положительные числа
- **Size Limits**: Защита от переполнения

## 🧪 Тестирование

### Запуск тестов

```bash
# Все тесты (Functional + API + Security)
docker-compose exec app ./vendor/bin/phpunit tests

# Только API-тесты (HTTP эндпоинты)
docker-compose exec app ./vendor/bin/phpunit tests/TaskApiTest.php

# Только функциональные тесты (репозиторий)
docker-compose exec app ./vendor/bin/phpunit tests/FunctionalTaskApiTest.php

# Тесты безопасности (XSS, CSRF, Validation)
docker-compose exec app ./vendor/bin/phpunit tests/SecurityTest.php

# Стресс-тесты (Edge Cases, Performance)
docker-compose exec app ./vendor/bin/phpunit tests/StressTest.php
```

### Покрытие тестами

- ✅ **Repository Layer**: CRUD операции с тестовой БД в памяти
- ✅ **HTTP Endpoints**: Эмуляция HTTP запросов через PHPUnit
- ✅ **Security Scenarios**: XSS, CSRF, SQL Injection, ID Validation
- ✅ **Edge Cases**: Огромные данные, невалидные форматы, race conditions

## ⚙️ Конфигурация

### Environment Variables

Создайте `.env` файл на основе `.env.example`:

```bash
cp .env.example .env
```

| Переменная | Описание | Default |
|------------|----------|---------|
| `DATABASE_PATH` | Путь к SQLite БД | `/var/www/html/database/app.sqlite` |
| `LOG_PATH` | Путь к файлу логов | `/var/www/html/logs/app.log` |
| `RATE_LIMIT_MAX` | Максимум запросов в окне | `100` |
| `RATE_LIMIT_WINDOW_SECONDS` | Размер окна в секундах | `60` |
| `APP_ENV` | Окружение | `local` |
| `APP_DEBUG` | Режим отладки | `true` |

## 📚 Документация API

### OpenAPI Specification

Полная спецификация API доступна в файле `openapi.yaml`.

**Инструменты для просмотра:**
- [Swagger Editor](https://editor.swagger.io/) - загрузите `openapi.yaml`
- [Swagger UI](https://swagger.io/tools/swagger-ui/) - интерактивная документация
- Postman - импортируйте `openapi.yaml`

### 📋 Эндпоинты

| Method | Endpoint | Description | Headers Required |
|--------|----------|-------------|------------------|
| `GET` | `/tasks` | Получить все задачи | - |
| `POST` | `/tasks` | Создать задачу | `X-Requested-With` |
| `GET` | `/tasks/{id}` | Получить задачу | - |
| `PUT` | `/tasks/{id}` | Обновить задачу | `X-Requested-With` |
| `DELETE` | `/tasks/{id}` | Удалить задачу | `X-Requested-With` |

### 🔐 Authentication Headers

Для всех mutating операций (POST/PUT/DELETE) обязательно:

```http
X-Requested-With: XMLHttpRequest
Content-Type: application/json
```

## 🏗️ Архитектура

```
src/
├── Controllers/          # HTTP обработчики
├── Database/            # PDO Factory
├── Http/               # JsonResponse
├── Logging/            # LoggerFactory
├── Middleware/         # CSRF, Rate Limit, Request Logging
├── Models/             # Task, TaskRepository
├── Services/           # RateLimiter
├── Support/            # Env loader
├── Validators/         # URL validation, sanitization
└── Router.php          # HTTP routing

tests/
├── FunctionalTaskApiTest.php  # Repository tests
├── TaskApiTest.php            # HTTP endpoint tests
├── SecurityTest.php          # Security scenarios
├── StressTest.php             # Edge cases
└── TestCase.php              # Base test class
```

## 🔧 Технический стек

- **Runtime**: PHP 8.2+
- **Database**: SQLite (production-ready)
- **Web Server**: Nginx + PHP-FPM
- **Testing**: PHPUnit 10.5
- **Containerization**: Docker + Docker Compose
- **Documentation**: OpenAPI 3.0

## 🚀 Production Deployment

### Security Recommendations

1. **HTTPS Only**: Обязательное использование SSL/TLS
2. **HSTS Header**: `Strict-Transport-Security: max-age=31536000; includeSubDomains`
3. **Environment Security**: Все секреты в `.env` файле
4. **Log Rotation**: Настройте ротацию логов
5. **Database**: Рассмотрите PostgreSQL/MySQL для high-load
6. **Monitoring**: Настройте алерты для rate limiting

### Environment Variables

```bash
APP_ENV=production
APP_DEBUG=false
RATE_LIMIT_MAX=60          # Более строгие лимиты для production
RATE_LIMIT_WINDOW_SECONDS=60
```

## 📊 OWASP Compliance

Приложение соответствует OWASP:

- ✅ **A01**: Broken Access Control - ID validation, CSRF protection
- ✅ **A02**: Cryptographic Failures - HTTPS enforcement
- ✅ **A03**: Injection - 100% prepared statements
- ✅ **A05**: Security Misconfiguration - Proper Nginx config
- ✅ **A07**: Identification & Authentication Failures - Rate limiting
- ✅ **A09**: Security Logging & Monitoring - Comprehensive error logging


**🛡️ Built with Security-First Approach**

*Этот API демонстрирует enterprise-уровень безопасности в PHP приложениях, следуя современным практикам защиты и стандартам OWASP.*
