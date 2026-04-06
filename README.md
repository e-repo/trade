# Symfony CQRS/DDD Template

Минималистичный шаблон проекта на **Symfony 7.4** с модульной архитектурой, реализующий паттерны **CQRS** (Command Query Responsibility Segregation) и **DDD** (Domain-Driven Design). Приложение контейнеризировано с помощью Docker и использует PostgreSQL 18.

## Основные технологии

- **PHP 8.4.19** - последняя версия PHP 8.4
- **Symfony 7.4** - актуальная LTS версия фреймворка
- **PostgreSQL 18** - современная версия СУБД
- **Doctrine ORM 3.6** - ORM для работы с базой данных
- **Docker** - контейнеризация (nginx, PHP-FPM, PHP-CLI, PostgreSQL, MailHog)
- **PHPUnit 13** - тестирование
- **Symfony Messenger** - шины для CQRS (command.bus, query.bus, event.bus)
- **Xdebug 3.4.7** - отладка

## Быстрый старт

### Первоначальная настройка
```bash
make be-init              # Полная инициализация: сборка, установка зависимостей, миграции
```

### Запуск и остановка
```bash
make b-up                 # Запустить все backend сервисы
make b-shell              # Войти в CLI контейнер
make down                 # Остановить все контейнеры
make ps                   # Показать запущенные контейнеры
```

### Доступ к сервисам
- **Приложение:** http://localhost:8088
- **API документация:** http://localhost:8088/api/doc
- **MailHog (почта):** http://localhost:8025
- **PostgreSQL:** localhost:54321 (пользователь: app, база: app)

## Архитектура проекта

### Модульная структура с низким зацеплением (Low Coupling)

Кодовая база следует строгой модульной архитектуре, где **модули взаимодействуют исключительно через события** (Event Bus). Это позволяет в будущем легко выделить модули в микросервисы.

**Текущие модули:**
- `SomeModule` - пример модуля с агрегатом Category, демонстрирующий CQRS/DDD паттерны
- `CoreKit` - общие утилиты и интерфейсы для всех модулей

**Правило изоляции модулей:** Модуль НЕ МОЖЕТ напрямую использовать классы из другого модуля (кроме CoreKit). Межмодульное взаимодействие происходит только через EventBus.

**Авторизация:** Данный шаблон не включает аутентификацию. Предполагается, что авторизация обрабатывается внешним Gateway сервисом.

### Четырехслойная архитектура модуля

Каждый модуль следует этой структуре:

```
Module/
├── UI/                    # HTTP контроллеры (Actions), Request/Response DTO
├── Application/           # Use cases: Commands, Queries, Listeners, Services
├── Domain/                # Бизнес-логика: Entities, Repositories (интерфейсы)
└── Infra/                 # Технические реализации: Repositories, Fetchers
```

**Поток зависимостей:** UI → Application → Domain ← Infra

### CQRS с Symfony Messenger

Три независимые шины сообщений обрабатывают разные задачи:

1. **command.bus** - операции записи (синхронные, транзакционные)
2. **query.bus** - операции чтения (синхронные)
3. **event.bus** - межмодульное взаимодействие (асинхронные)

**Соглашение по обработчикам:**
- Все обработчики реализуют `CommandHandlerInterface`, `QueryHandlerInterface` или `EventListenerInterface`
- Обработчики автоматически тегируются через `_instanceof` в services.yaml
- Файл обработчика всегда называется `Handler.php` с методом `__invoke()`

### Структура HTTP endpoints (Actions)

HTTP эндпоинты следуют единообразному паттерну:

```
UI/Http/V1/{Aggregate}/{Action}/
├── Action.php          # Контроллер с route, security, OpenAPI аннотациями
├── Request.php         # DTO с валидационными ограничениями
└── Response.php        # Response DTO
```

**Пример:**
```php
// Action.php
#[Route('/api/v1/module/category', methods: ['POST'])]
#[OA\Post(summary: 'Create category')]
class Action extends AbstractController
{
    public function __invoke(Request $request, CommandBusInterface $bus): JsonResponse
    {
        // Отправка команды в шину
    }
}
```

### Организация схем базы данных

Каждый модуль имеет собственную PostgreSQL схему:
- `module` - таблицы SomeModule (например: Category)
- `corekit` - общие таблицы

Миграции организованы по модулям в `migrations/{Module}/`.

## Работа с проектом

### Команды внутри CLI контейнера

```bash
# Миграции
php bin/console do:mi:mi --no-interaction           # Применить миграции
php bin/console do:mi:diff                          # Сгенерировать миграцию
php bin/console do:mi:gene                          # Создать пустую миграцию

# Тестирование
php bin/phpunit                                     # Запустить все тесты
php bin/phpunit --filter=CreateCategoryTest         # Запустить конкретный тест
php bin/phpunit --testsuite=Integration             # Запустить интеграционные тесты

# Кеш
php bin/console cache:clear                         # Очистить кеш
php bin/console cache:pool:clear cache.app          # Очистить конкретный пул

# База данных
php bin/console doctrine:schema:validate            # Валидация схемы
php bin/console doctrine:query:sql "SELECT * FROM module.category"
```

### Команды с хоста (вне контейнера)

```bash
make t-test               # Запустить тесты извне контейнера
make t-migrations         # Применить миграции извне контейнера
make t-composer-install   # Установить composer зависимости
```

### Добавление новой функции в существующий модуль

1. **Определить агрегат** (например, Post, Category)
2. **Создать структуру use case:**
   ```
   Application/{Aggregate}/Command/{Action}/
   ├── Command.php
   └── Handler.php
   ```
3. **Добавить HTTP endpoint** в `UI/Http/V1/{Aggregate}/{Action}/`
4. **Обновить домен** при необходимости (Entity, интерфейс Repository)
5. **Реализовать инфраструктуру** (методы Repository в Infra)
6. **Написать тесты** в `tests/Test/Integration/`

### Создание нового модуля

1. Создать структуру директорий: `src/{Module}/{UI,Application,Domain,Infra}`
2. Зарегистрировать namespace в `composer.json` autoload
3. Зарегистрировать сервисы в `config/services.yaml`
4. Создать схему базы данных: `{module_name}` в миграциях
5. Следовать правилу низкого зацепления: взаимодействие с другими модулями только через EventBus

### Работа с миграциями

Миграции группируются по модулям. Генерация:
```bash
php bin/console do:mi:diff
# Отредактировать сгенерированную миграцию для указания схемы
# Добавить: $this->addSql('SET search_path TO module');
php bin/console do:mi:mi --no-interaction
```

## Соглашения и стандарты

### Именование файлов
- **Commands:** `{Action}Command.php` (например, `CreateCommand.php`)
- **Queries:** `{Operation}Query.php` (например, `GetListQuery.php`)
- **Handlers:** Всегда `Handler.php`
- **Actions:** Всегда `Action.php`
- **Repositories:** `{Entity}Repository.php` с интерфейсом в Domain, реализацией в Infra

### Стандарты кода
- PHP 8.4+ со строгими типами (`declare(strict_types=1);`)
- Стандарт кодирования PSR-12, проверяется ECS
- Pre-commit хуки через CaptainHook запускают проверки ECS
- Использование readonly свойств для неизменяемых DTO
- Все entity используют UUID первичные ключи (кастомный тип `Id`)

### Валидация
- Request DTO используют ограничения Symfony Validator
- Кастомный валидатор: `@NotWhitespace` предотвращает строки только с пробелами
- Сообщения валидации на русском (см. `translations/validators.ru.yaml`)

### Обработка исключений
- **DomainException** → HTTP 422 (нарушения бизнес-логики)
- **NotFoundException** → HTTP 404
- **AccessDeniedException** → HTTP 403
- Исключения мапятся централизованно в `config/packages/exceptions.yaml`

### Формат ответов
Все API ответы используют `ResponseWrapper`:
```json
{
  "data": { /* данные ответа */ },
  "violations": [
    {
      "source": "field_name",
      "detail": "Сообщение об ошибке",
      "data": {}
    }
  ]
}
```

## Тестирование

Тесты наследуются от `FunctionalTestCase` из `tests/Test/Common/`:
- Используют `DatabaseToolCollection` для фикстур
- Фикстуры именуются: `{Entity}Fixture.php`
- DAMA Doctrine Bundle обеспечивает транзакционную изоляцию тестов
- Тестовая база данных создается автоматически через `docker/development/create-test-database.sh`

**Структура теста:**
```php
public function test_success(): void
{
    // Arrange
    $this->loadFixtures([CategoryFixture::class]);

    // Act
    $response = $this->sendRequest('POST', '/api/v1/module/category', $data);

    // Assert
    $this->assertResponseIsSuccessful();
}
```

## Отладка

- **Xdebug** включен в CLI и FPM контейнерах
- Имя сервера: `bb.local` (настроить в IDE)
- Используйте `dd()` или `dump()` из Symfony VarDumper
- Проверка логов: `var/log/dev.log`

## Контроль качества кода

Запуск ECS перед коммитом:
```bash
vendor/bin/ecs check src
vendor/bin/ecs check src --fix    # Автоматическое исправление
```

CaptainHook автоматически запускается при `git commit` для соблюдения стандартов.

## Конфигурация окружения

**Переменные окружения PHP-FPM:**
- `clear_env = no` установлено в `docker/development/fpm/pool.d/www.conf`
- Это означает, что ВСЕ переменные окружения контейнера доступны в PHP
- Не требуется явно передавать переменные через pool config

**Ключевые переменные окружения:**
- `DATABASE_URL` - подключение к PostgreSQL
- `APP_ENV`, `APP_SECRET`
- CORS, Mailer DSN настраиваются в `.env`

## Идея модульной архитектуры

Разбивка на модули (контексты) решает проблему **быстрого способа выделения модуля в отдельный сервис** при масштабировании проекта. На начальном этапе это может показаться избыточной абстракцией, но взаимодействие через EventBus дает возможность быстрого перехода от синхронного взаимодействия модулей к брокеру сообщений (например, Kafka).

**Gateway** (в процессе проработки) решает проблему сбора данных из нескольких модулей в одном запросе от клиента.

