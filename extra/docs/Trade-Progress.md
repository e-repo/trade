# Trade Module - Progress Tracker

## 📍 Текущий статус разработки

**Дата последнего обновления:** 2026-05-25
**Текущий этап:** Endpoint #1 (POST /api/v1/trade/lot) - полностью завершен (реализация + тесты). Готов к Endpoint #2

---

## ✅ Что уже выполнено

### Подготовительный этап (COMPLETED)

1. **Структура модуля Trade** ✅ **РЕОРГАНИЗОВАНА 2026-05-21**
   - ✅ Новая структура директорий:
     ```
     Trade/
     ├── Domain/
     │   ├── Dictionary/        # Справочники
     │   │   ├── Entity/        # CargoType, Contractor, VolumeStep
     │   │   └── Repository/    # Repository Interfaces
     │   └── Lot/               # Агрегат Lot
     │       ├── Entity/        # (готово для разработки)
     │       ├── Repository/    # (готово для разработки)
     │       ├── Enum/          # (готово для разработки)
     │       └── ValueObject/   # (готово для разработки)
     └── Infra/
         ├── Dictionary/
         │   └── Repository/    # Реализации репозиториев справочников
         └── Lot/
             └── Repository/    # (готово для разработки)
     ```
   - ✅ Все namespace обновлены: `Trade\Domain\Dictionary\*`, `Trade\Infra\Dictionary\*`
   - ✅ Старая структура `Trade/Domain/Trade/` удалена

2. **Регистрация модуля**
   - ✅ Namespace `Trade\` добавлен в `composer.json`
   - ✅ Сервисы зарегистрированы в `config/services.yaml`
   - ✅ Выполнен `composer dump-autoload`

3. **Справочные сущности (Domain/Dictionary)**
   - ✅ **CargoType** - тип груза
   - ✅ **Contractor** - подрядчик (перевозчик)
   - ✅ **VolumeStep** - шаг объёма (грузоподъёмность)
   - ✅ Repository Interfaces для всех справочников
   - ✅ Repository Implementations (Infra/Dictionary)

4. **Database Migrations**
   - ✅ `Version20260405133313` - создание таблиц справочников (cargo_type, contractor, volume_step)
   - ✅ `Version20260406055047` - seed данных:
     - 1 тип груза: "Семена подсолнечника"
     - 1 шаг объёма: 25 тонн
     - 20 тестовых контрагентов

5. **Doctrine Schema**
   - ✅ Валидация пройдена: `doctrine:schema:validate` - OK
   - ✅ В `config/packages/doctrine.yaml` удален mapping для `SomeModule` (чтобы не генерировать его таблицы в Trade миграциях)

6. **Документация**
   - ✅ `CLAUDE.md` обновлен с новой структурой Trade модуля

---

## ✅ Endpoint #1: POST /api/v1/trade/lot (ПОЛНОСТЬЮ ЗАВЕРШЕН)

**Статус:** ✅ **ЗАВЕРШЕН: РЕАЛИЗАЦИЯ + ТЕСТИРОВАНИЕ (2026-05-25)**

**Что реализовано:**

### 1. Domain Layer ✅

- ✅ **Enums** (`src/Trade/Domain/Lot/Enum/`):
  - `LotStatusEnum` (CREATED, OPEN, CLOSED)
  - `CloseReasonEnum` (EXPIRED, MANUAL)

- ✅ **Value Objects** (`src/Trade/Domain/Lot/ValueObject/`):
  - `Volume` - управление объёмом с инвариантами:
    - Максимум 100,000 тонн
    - Кратность volumeStep
    - Метод `reserve(int)` с валидацией
    - Методы: `getTotalVolume()`, `getReservedVolume()`, `getFreeVolume()`
  - `Price` - стартовая цена и шаг (в копейках)
    - Методы: `getStartPrice()`, `getPriceStep()`
  - `LotTermination` - время закрытия и причина
    - Методы: `getClosesAt()`, `getCloseReason()`, `close(CloseReasonEnum)`

- ✅ **Entity** (`src/Trade/Domain/Lot/Entity/Lot.php`):
  - Aggregate Root с индексами: `idx_lot_status`, `idx_lot_opens_at`, `idx_lot_closes_at`
  - Конструктор с валидацией дат (opensAt < closesAt, closesAt в будущем)
  - Foreign keys на CargoType и VolumeStep
  - Методы: `getId()`, `getStatus()`, `getFreeVolume()`, `canAcceptBids()`
  - Статус по умолчанию: CREATED

- ✅ **Repository Interface** (`src/Trade/Domain/Lot/Repository/LotRepositoryInterface.php`):
  - `add(Lot): void`
  - `get(Id): Lot`

### 2. Infra Layer ✅

- ✅ **LotRepository** (`src/Trade/Infra/Lot/Repository/LotRepository.php`):
  - Реализует LotRepositoryInterface
  - Использует EntityManagerInterface
  - Метод `get()` бросает NotFoundException если лот не найден

### 3. Application Layer ✅

- ✅ **Command** (`src/Trade/Application/Lot/Command/Create/Command.php`):
  - DTO с параметрами в camelCase
  - Поля: cargoTypeId, totalVolume, startPrice, priceStep, volumeStepId, opensAt, closesAt

- ✅ **Result** (`src/Trade/Application/Lot/Command/Create/Result.php`):
  - Возвращаемый DTO: lotId (string), status (string)

- ✅ **Handler** (`src/Trade/Application/Lot/Command/Create/Handler.php`):
  - Реализует CommandHandlerInterface
  - Валидирует существование CargoType и VolumeStep через репозитории
  - Создаёт Lot в статусе CREATED
  - Возвращает Result (не доменную сущность!)

### 4. UI Layer (HTTP API) ✅

- ✅ **Request** (`src/Trade/UI/Http/V1/Lot/Create/Request.php`):
  - Реализует RequestPayloadInterface
  - Валидации: Uuid, Positive, NotBlank
  - OpenAPI аннотации с примерами
  - camelCase properties
  - **Unix timestamp (int)** для opensAt/closesAt

- ✅ **Response** (`src/Trade/UI/Http/V1/Lot/Create/Response.php`):
  - Реализует ResponseInterface
  - Структура: `{lotId: string, status: string}`

- ✅ **Action** (`src/Trade/UI/Http/V1/Lot/Create/Action.php`):
  - Route: `POST /api/v1/trade/lot` (name: `trade_create-lot`)
  - OpenAPI документация (tag: `Trade - Lots`)
  - Конвертация Unix timestamp → DateTimeImmutable
  - Маппинг Result → Response
  - Обёртка ResponseWrapper

### 5. Database Migration ✅

- ✅ **Version20260521073725** (`migrations/Trade/2026/05/Version20260521073725.php`):
  - Таблица `trade.lot` создана со всеми полями
  - Embeddables: volume (total_volume, reserved_volume), price (start_price, price_step), termination (closes_at, close_reason)
  - Foreign keys: cargo_type_id → cargo_type, volume_step_id → volume_step
  - Индексы: `idx_lot_status`, `idx_lot_opens_at`, `idx_lot_closes_at` ✅
  - Миграция применена: `doctrine:schema:validate` - OK ✅

### 6. Integration Tests ✅

**Директория:** `tests/Test/Integration/Trade/Api/CreateLot/`

**Фикстуры созданы:**
- ✅ `CargoTypeFixture.php` - использует UUID из seed-миграции (550e8400-e29b-41d4-a716-446655440001)
- ✅ `VolumeStepFixture.php` - использует UUID из seed-миграции (550e8400-e29b-41d4-a716-446655440010, value: 25)
- ✅ `ContractorFixture.php` - использует UUID из seed-миграции (550e8400-e29b-41d4-a716-446655440020)
- ✅ Базовые классы фикстур: `BaseCargoTypeFixture`, `BaseVolumeStepFixture`, `BaseContractorFixture`

**Тесты реализованы (12 сценариев, 45 assertions):**
1. ✅ `testSuccessCreateLot` - успешное создание лота (HTTP 200)
2. ✅ `testFailedByInvalidCargoTypeUuid` - невалидный UUID cargoTypeId (HTTP 400)
3. ✅ `testFailedByInvalidVolumeStepUuid` - невалидный UUID volumeStepId (HTTP 400)
4. ✅ `testFailedByNegativeTotalVolume` - отрицательный totalVolume (HTTP 400)
5. ✅ `testFailedByNegativeStartPrice` - отрицательный startPrice (HTTP 400)
6. ✅ `testFailedByNegativePriceStep` - отрицательный priceStep (HTTP 400)
7. ✅ `testFailedByNonExistentCargoType` - несуществующий CargoType (HTTP 404)
8. ✅ `testFailedByNonExistentVolumeStep` - несуществующий VolumeStep (HTTP 404)
9. ✅ `testFailedByOpensAtAfterClosesAt` - opensAt после closesAt (HTTP 422)
10. ✅ `testFailedByOpensAtEqualsClosesAt` - opensAt равен closesAt (HTTP 422)
11. ✅ `testFailedByClosesAtInThePast` - closesAt в прошлом (HTTP 422)
12. ✅ `testFailedByVolumeNotMultipleOfVolumeStep` - объем не кратен volumeStep (HTTP 422)

**Результат:** ✅ Все тесты проходят успешно (Tests: 12, Assertions: 45)

### 7. Bug Fixes ✅

- ✅ Исправлен `Handler.php:40` - изменено `$lot->getId()->getValue()` → `$lot->getId()->value`
- ✅ Добавлена регистрация роутов Trade модуля в `config/routes.yaml`

---

## ⏳ Что нужно реализовать дальше (ТЕКУЩАЯ ЗАДАЧА)

### Endpoint #2: POST /api/v1/trade/bid (Размещение ставки)

**Статус:** ❌ НЕ НАЧАТО - следующий этап разработки

**Описание:**
Самый сложный endpoint модуля Trade. Требует реализации:
- Bid entity с валидацией
- Strategy Pattern для распределения объёма
- BidCollection для управления коллекцией ставок
- Пессимистические блокировки (SELECT ... FOR UPDATE)
- Конкурентное распределение объёма между ставками

**См. детали в:**
- `extra/docs/Trade-Development-Plan.md` (раздел Endpoint #2)
- `extra/docs/Auction-Algorithm-Implementation.md` (тактический DDD подход)

---

## 📋 Команды для начала работы

### Запуск окружения:
```bash
make b-up                    # Запустить все сервисы
make b-shell                 # Войти в CLI контейнер
```

### Внутри контейнера:
```bash
# Проверить миграции
php bin/console do:mi:status

# Проверить схему
php bin/console doctrine:schema:validate

# Запустить тесты
php bin/phpunit tests/Test/Integration/Trade/Lot/CreateLot/CreateLotTest.php --testdox

# Проверить код стиль
vendor/bin/ecs check src/Trade
```

---

## 🎯 Следующие этапы:

1. **Endpoint #1:** POST /api/v1/trade/lot - ✅ **ЗАВЕРШЕН (2026-05-25)**
   - ✅ Логика реализована и ревью пройдено
   - ✅ Фикстуры созданы (CargoType, VolumeStep, Contractor)
   - ✅ Интеграционные тесты написаны (12 тестов, 45 assertions)
   - ✅ Все тесты проходят успешно
   - ✅ Bug fixes: Handler.php, routes.yaml

2. **Endpoint #2:** POST /api/v1/trade/bid (Размещение ставки) - **СЛЕДУЮЩАЯ ЗАДАЧА**
   - Самый сложный endpoint с конкурентным распределением объёма
   - Требует реализации Bid entity, Strategy Pattern, BidCollection
   - Пессимистические блокировки (SELECT ... FOR UPDATE)

3. **Endpoint #3:** GET /api/v1/trade/lot/{id} (Получить лот)

4. **Endpoint #4:** GET /api/v1/trade/lot (Список лотов)

---

## 📚 Важные файлы для контекста:

- `extra/docs/Trade.md` - полное описание модуля и требования
- `extra/docs/Trade-Development-Plan.md` - пошаговый план разработки
- `extra/docs/Auction-Algorithm-Implementation.md` - тактический DDD подход
- `CLAUDE.md` - архитектурные принципы проекта

---

## 🔑 Ключевые особенности реализации:

1. **CQRS подход:**
   - Command возвращает Result (Application DTO)
   - Action делает маппинг Result → Response
   - Доменная сущность не светится на UI слое

2. **Валидация:**
   - Первичная: Symfony validators в Request DTO
   - Вторичная: бизнес-правила в конструкторе Lot (`validateDates()`)

3. **Статус из enum:**
   - Не хардкодим строки
   - `$lot->getStatus()->value` вместо `'CREATED'`

4. **Naming convention:**
   - camelCase для PHP properties
   - snake_case для БД (автоматический маппинг)

---

## 💡 Prompt для возобновления работы:

```
Мы разрабатываем модуль Trade (сервис торгов) на Symfony 7.4 с CQRS/DDD.

**Текущий статус:**
- ✅ Подготовительный этап завершен (справочники: CargoType, Contractor, VolumeStep)
- ✅ Структура модуля Trade реорганизована (2026-05-21)
- ✅ Endpoint #1: POST /api/v1/trade/lot - ПОЛНОСТЬЮ ЗАВЕРШЕН (2026-05-25):
  - ✅ Domain Layer: Enums, Value Objects (Volume, Price, LotTermination), Lot Entity
  - ✅ Application Layer: Command, Handler, Result
  - ✅ UI Layer: Request, Response, Action
  - ✅ Infra Layer: LotRepository
  - ✅ Migration: Version20260521073725 (таблица trade.lot с индексами)
  - ✅ Интеграционные тесты: 12 тестов, 45 assertions - все проходят
  - ✅ Bug fixes: Handler.php, routes.yaml
- ⏸️ **ОСТАНОВИЛИСЬ НА:** Endpoint #1 завершен, готовы к Endpoint #2

**Следующий шаг (ТЕКУЩАЯ ЗАДАЧА):**
Начать разработку Endpoint #2: POST /api/v1/trade/bid (Размещение ставки)

**Что нужно реализовать:**
1. **Domain Layer:**
   - Bid Entity (агрегат со ставкой)
   - BidStatusEnum (ACTIVE, WINNER, LOSER)
   - BidCollection для управления коллекцией ставок
   - Strategy Pattern для распределения объёма

2. **Application Layer:**
   - PlaceBid/Command.php
   - PlaceBid/Handler.php (с пессимистическими блокировками)
   - PlaceBid/Result.php

3. **UI Layer:**
   - PlaceBid/Request.php
   - PlaceBid/Response.php
   - PlaceBid/Action.php

4. **Infra Layer:**
   - BidRepository (с SELECT ... FOR UPDATE)

5. **Tests:**
   - Интеграционные тесты для всех сценариев размещения ставки

**Особенности реализации:**
- Пессимистические блокировки для конкурентного доступа
- Алгоритм распределения объёма между ставками
- Валидация бизнес-правил (лот открыт, достаточно свободного объёма)

**См. детали в:**
- extra/docs/Trade-Progress.md (этот файл)
- extra/docs/Trade-Development-Plan.md (раздел Endpoint #2)
- extra/docs/Trade.md (требования к ставкам)
- extra/docs/Auction-Algorithm-Implementation.md (алгоритм аукциона, Strategy Pattern)
```

---

**Дата создания:** 2026-04-26
**Дата последнего обновления:** 2026-05-25 (завершен Endpoint #1 с тестами)
