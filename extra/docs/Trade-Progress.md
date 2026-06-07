# Trade Module - Progress Tracker

## 📍 Текущий статус разработки

**Дата последнего обновления:** 2026-06-07
**Текущий этап:** Все основные функции реализованы, модуль Trade полностью функционален

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

## ✅ Endpoint #2: POST /api/v1/trade/bid (ПОЛНОСТЬЮ ЗАВЕРШЕН)

**Статус:** ✅ **ЗАВЕРШЕН: РЕАЛИЗАЦИЯ + ТЕСТИРОВАНИЕ (2026-05-27)**

**Что реализовано:**

### 1. Domain Layer ✅

- ✅ **Enums** (`src/Trade/Domain/Lot/Enum/`):
  - `BidStatusEnum` (PENDING, ACTIVE, PARTIALLY_ACTIVE, OUTBID, REJECTED)

- ✅ **Entity** (`src/Trade/Domain/Lot/Entity/Bid.php`):
  - Aggregate с самомодификацией через методы:
    - `createPending()` - фабричный метод создания ставки
    - `allocate(int)` - выделить объём ставке
    - `displace(int)` - вытеснить ставку (полностью или частично)
    - `reject(string)` - отклонить ставку
  - Foreign keys на Lot (CASCADE DELETE) и Contractor
  - Индексы: `idx_bid_lot_id`, `idx_bid_contractor_id`, `idx_bid_lot_price_allocated`
  - Getters: `getId()`, `getAllocatedVolume()`, `getPricePerTon()`, `hasAllocatedVolume()`, `isAccepted()`

- ✅ **Collection** (`src/Trade/Domain/Lot/Collection/BidCollection.php`):
  - Value Object для работы с коллекцией ставок
  - Методы:
    - `getWorseThan(int)` - получить ставки с ценой выше заданной (для вытеснения)
    - `getTotalAllocatedVolume()` - суммарный выделенный объём
    - `add(Bid)`, `toArray()`, `count()`
  - Implements: `IteratorAggregate`, `Countable`

- ✅ **Strategy Pattern** (`src/Trade/Domain/Lot/Strategy/`):
  - `BidAllocationStrategyInterface` - интерфейс стратегии размещения
  - `PriceBasedAllocationStrategy` - обратный аукцион (выигрывают дешевые):
    - Алгоритм: сначала свободный объём, затем вытеснение худших ставок
    - Приоритет вытеснения: дороже цена → раньше вытесняется
    - При равной цене: LIFO (новые вытесняются первыми)
  - `AllocationResult` - Result Object стратегии

- ✅ **Result Object** (`src/Trade/Domain/Lot/Result/BidPlacementResult.php`):
  - Результат размещения ставки с методами:
    - `isSuccess()` - проверка успешности
    - `getAllBidsToSave()` - все ставки для сохранения

- ✅ **Lot Entity обновлен**:
  - Добавлен метод `placeBid()` - центральная бизнес-операция размещения ставки
  - Проверка инварианта: `reserved_volume <= total_volume`

- ✅ **Volume Value Object обновлен**:
  - Добавлен метод `setReservedVolume(int)` с валидацией

- ✅ **Repository Interfaces**:
  - `BidRepositoryInterface` - методы: `add()`, `get()`, `findActiveBidsForLotWithLock()`
  - `LotRepositoryInterface` - добавлен метод `lockForUpdate()` для пессимистической блокировки

### 2. Infra Layer ✅

- ✅ **BidRepository** (`src/Trade/Infra/Lot/Repository/BidRepository.php`):
  - Реализует BidRepositoryInterface
  - `findActiveBidsForLotWithLock()` - SELECT ... FOR UPDATE для конкурентного доступа
  - LockMode::PESSIMISTIC_WRITE

- ✅ **LotRepository** обновлен:
  - Добавлен метод `lockForUpdate()` с пессимистической блокировкой

### 3. Application Layer ✅

- ✅ **Command** (`src/Trade/Application/Bid/Command/PlaceBid/Command.php`):
  - DTO с параметрами: lotId, contractorId, requestedVolume, pricePerTon

- ✅ **Result** (`src/Trade/Application/Bid/Command/PlaceBid/Result.php`):
  - Возвращаемый DTO: bidId, status, allocatedVolume, requestedVolume

- ✅ **Handler** (`src/Trade/Application/Bid/Command/PlaceBid/Handler.php`):
  - Реализует CommandHandlerInterface
  - Использует пессимистические блокировки (lockForUpdate)
  - Вызывает бизнес-операцию `$lot->placeBid()`
  - Сохраняет новую ставку и вытесненные ставки

### 4. UI Layer (HTTP API) ✅

- ✅ **Request** (`src/Trade/UI/Http/V1/Bid/PlaceBid/Request.php`):
  - Реализует RequestPayloadInterface
  - Валидации: Uuid, Positive, NotBlank
  - OpenAPI аннотации с примерами
  - camelCase properties

- ✅ **Response** (`src/Trade/UI/Http/V1/Bid/PlaceBid/Response.php`):
  - Реализует ResponseInterface
  - Структура: `{bidId, status, allocatedVolume, requestedVolume}`

- ✅ **Action** (`src/Trade/UI/Http/V1/Bid/PlaceBid/Action.php`):
  - Route: `POST /api/v1/trade/bid` (name: `trade_place-bid`)
  - OpenAPI документация (tag: `Trade - Bids`)
  - Читает `x-user-id` header для contractorId
  - Маппинг Result → Response
  - Обёртка ResponseWrapper

### 5. Database Migration ✅

- ✅ **Version20260525075629** (`migrations/Trade/2026/05/Version20260525075629.php`):
  - Таблица `trade.bid` создана со всеми полями
  - Поля: id, lot_id, contractor_id, requested_volume, allocated_volume, price_per_ton, status, rejection_reason, created_at, updated_at
  - Foreign keys: lot_id → lot (CASCADE DELETE), contractor_id → contractor
  - Индексы: `idx_bid_lot_id`, `idx_bid_contractor_id`, `idx_bid_lot_price_allocated`
  - Миграция применена: `doctrine:schema:validate` - OK ✅

### 6. Configuration ✅

- ✅ **services.yaml** обновлен:
  - Зарегистрирован алиас `BidAllocationStrategyInterface` → `PriceBasedAllocationStrategy`

### 7. Integration Tests ✅

**Директория:** `tests/Test/Integration/Trade/Api/PlaceBid/`

**Фикстуры созданы:**
- ✅ `BaseLotFixture.php` - базовый класс для создания лотов с помощью reflection (для установки статуса OPEN)
- ✅ `LotFixture.php` - открытый лот (1000 тонн, статус OPEN)
- ✅ `CargoTypeFixture.php`, `VolumeStepFixture.php`, `ContractorFixture.php` - локальные фикстуры

**Тесты реализованы (7 успешных сценариев, 56 assertions):**
1. ✅ `testSuccessPlaceBid` - базовое размещение ставки со свободным объемом
2. ✅ `testSuccessPlaceBidWithPartialAllocation` - частичное выделение (allocated_volume < requested_volume)
3. ✅ `testSuccessPlaceBidDisplacingWorseOnes` - вытеснение дорогих ставок (полное вытеснение + частичное)
4. ✅ `testSuccessPlaceBidPartialDisplacement` - частичное вытеснение одной ставки
5. ✅ `testSuccessPlaceBidWithSamePriceLIFO` - отклонение при одинаковой цене (LIFO правило)
6. ✅ `testSuccessPlaceBidForEntireLotVolume` - резервирование всего объема лота (1000 тонн)
7. ✅ `testSuccessMultipleBidsFromSameContractor` - несколько ставок от одного подрядчика

**Результат:** ✅ Все тесты проходят успешно (Tests: 7, Assertions: 56)

**Bug fixes в процессе тестирования:**
- ✅ `BidRepository.php:47` - исправлен вызов setLockMode() на Query вместо QueryBuilder
- ✅ `PriceBasedAllocationStrategy.php:50` - исправлен расчет totalReservedVolume (убрана двойная субтракция)

---

## ✅ Console Command #1: trade:open-lots (ПОЛНОСТЬЮ ЗАВЕРШЕН)

**Статус:** ✅ **ЗАВЕРШЕН: РЕАЛИЗАЦИЯ + РЕФАКТОРИНГ + ТЕСТИРОВАНИЕ (2026-06-01)**

**Назначение:** Автоматически открывать лоты по расписанию (CREATED → OPEN)

**Что реализовано:**

### 1. Domain Layer ✅

- ✅ **DomainEventInterface** (`src/Trade/Domain/Event/DomainEventInterface.php`):
  - Маркерный интерфейс для всех доменных событий (для Symfony Messenger)

- ✅ **LotOpenedEvent** (`src/Trade/Domain/Event/LotOpenedEvent.php`):
  - Доменное событие с полями: lotId, openedAt
  - Реализует DomainEventInterface

- ✅ **Lot::open()** (`src/Trade/Domain/Lot/Entity/Lot.php:108`):
  - Бизнес-метод открытия лота
  - Валидация: статус должен быть CREATED, opensAt должно наступить
  - Переводит статус в OPEN

- ✅ **LotRepositoryInterface::findLotsToOpen()** (`src/Trade/Domain/Lot/Repository/LotRepositoryInterface.php`):
  - Метод поиска лотов для открытия

### 2. Infra Layer ✅

- ✅ **LotRepository::findLotsToOpen()** (`src/Trade/Infra/Lot/Repository/LotRepository.php:53`):
  - DQL запрос: WHERE status = CREATED AND opensAt <= :now
  - Возвращает массив лотов для открытия

### 3. Application Layer ✅

- ✅ **Open\Command** (`src/Trade/Application/Lot/Command/Open/Command.php`):
  - CQRS команда с параметром lotId для открытия одного лота

- ✅ **Open\Handler** (`src/Trade/Application/Lot/Command/Open/Handler.php`):
  - Вызывает `$lot->open()`
  - Публикует `LotOpenedEvent` в event.bus

- ✅ **OpenDueLots\Command** (`src/Trade/Application/Lot/Command/OpenDueLots/Command.php`):
  - CQRS команда с параметром `now: DateTimeImmutable`
  - Используется для массового открытия лотов по расписанию

- ✅ **OpenDueLots\Handler** (`src/Trade/Application/Lot/Command/OpenDueLots/Handler.php`):
  - Находит лоты через `findLotsToOpen()`
  - Для каждого лота диспатчит `Open\Command`
  - Обрабатывает ошибки с логированием
  - Возвращает `Result` со статистикой (totalProcessed, successfullyOpened, failed)

- ✅ **OpenDueLots\Result** (`src/Trade/Application/Lot/Command/OpenDueLots/Result.php`):
  - DTO для возврата статистики выполнения

- ✅ **EventLoggerListener** (`src/Trade/Application/Listener/EventLoggerListener.php`):
  - Универсальный слушатель для всех доменных событий
  - **Использует Symfony Serializer** для сериализации событий (вместо Reflection)
  - Логирует в `var/log/events.log` в JSON формате

### 4. UI Layer (Console) ✅

- ✅ **OpenLotsCommand** (`src/Trade/UI/Console/OpenLotsCommand.php`):
  - Консольная команда `trade:open-lots`
  - **Рефакторинг**: убрана прямая зависимость от `LotRepositoryInterface`
  - Диспатчит `OpenDueLots\Command` через CommandBus
  - Получает `Result` и выводит статистику
  - Exit code: 0 (success), 1 (failures)

### 5. Configuration ✅

- ✅ **monolog.yaml**:
  - Создан канал `event_bus`
  - Настроен handler `event_log` → `var/log/events.log` (JSON формат)
  - Канал `event_bus` исключен из основных логов (app, console)

- ✅ **services.yaml**:
  - Зарегистрирован `EventLoggerListener` с логгером `@monolog.logger.event_bus`
  - Автоматический тег `messenger.message_handler` для event.bus через `_instanceof`

- ✅ **messenger.yaml** (when@test):
  - Добавлен транспорт `event.bus: 'test://'` для тестирования
  - Настроен routing доменных событий в транспорт

### 6. Integration Tests ✅

**Директория:** `tests/Test/Integration/Trade/Console/OpenLots/`

**Фикстуры созданы:**
- ✅ `CargoTypeFixture.php` - тип груза
- ✅ `VolumeStepFixture.php` - шаг объёма (25 тонн)
- ✅ `LotFixture.php` - 3 лота (2 готовых к открытию, 1 с будущим opensAt)

**Тесты реализованы (5 сценариев, 29 assertions):**
1. ✅ `testSuccessOpenMultipleLots` - успешное открытие 2 лотов + проверка событий через transport
2. ✅ `testNoLotsToOpen` - нет лотов для открытия
3. ✅ `testOnlyLotsWithPassedOpensAtAreOpened` - открываются только лоты с наступившим opensAt
4. ✅ `testAlreadyOpenedLotsAreSkipped` - уже открытые лоты пропускаются
5. ✅ `testEventDataContainsCorrectInformation` - проверка данных в событиях (lotId, openedAt)

**Результат:** ✅ Все тесты проходят успешно (Tests: 5, Assertions: 29)

### 7. Bug Fixes & Improvements ✅

- ✅ Исправлен PlaceBid/Request.php - убрана дублирующая схема `#[OA\Schema]`
- ✅ Исправлен PlaceBid/Response.php - аннотации перемещены на свойства
- ✅ Обновлен PlaceBid/Action.php - описание requestBody и response inline
- ✅ OpenAPI документация работает корректно (http://localhost:8088/api/doc)

**Команда зарегистрирована:** ✅ `php bin/console trade:open-lots`

### 8. Архитектурные улучшения (Рефакторинг 2026-06-01) ✅

**Проблемы до рефакторинга:**
- ❌ UI слой (OpenLotsCommand) напрямую зависел от Repository
- ❌ Нарушение слоистой архитектуры (UI → Repository минуя Application)
- ❌ EventLoggerListener использовал кастомную сериализацию через Reflection
- ❌ Отсутствовала возможность тестирования событий

**Решение:**
- ✅ **Создан OpenDueLots\Command + Handler в Application слое**
  - Логика поиска лотов инкапсулирована в Handler
  - UI слой только диспатчит команду через CommandBus
  - Handler возвращает Result со статистикой выполнения

- ✅ **Использование Symfony Serializer в EventLoggerListener**
  - Удалены методы `serializeEvent()` и `serializeValue()`
  - Использование `$serializer->normalize($event)` - стандартное решение
  - Код стал чище и проще в поддержке

- ✅ **Конфигурация Messenger для тестов**
  - Добавлен транспорт `event.bus: 'test://'` в when@test
  - Routing доменных событий в транспорт для проверки в тестах
  - Возможность проверки количества и содержимого событий

**Результат:**
- ✅ Соблюдение принципов Clean Architecture
- ✅ UI → Application → Domain ← Infra (правильный поток зависимостей)
- ✅ Покрытие тестами: 24 теста, 130 assertions для всего модуля Trade
- ✅ Код соответствует архитектурным стандартам проекта (CLAUDE.md)

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
   - ✅ Реализация + интеграционные тесты (12 тестов, 45 assertions)

2. **Endpoint #2:** POST /api/v1/trade/bid - ✅ **ЗАВЕРШЕН (2026-05-27)**
   - ✅ Реализация + интеграционные тесты (7 тестов, 56 assertions)
   - ✅ Bug fixes: BidRepository.php, PriceBasedAllocationStrategy.php

3. **Console Command #1:** trade:open-lots - ✅ **ЗАВЕРШЕН (2026-06-01)**
   - ✅ Domain: Lot::open(), LotOpenedEvent, DomainEventInterface
   - ✅ Application: Open Command/Handler, OpenDueLots Command/Handler/Result
   - ✅ Infra: LotRepository::findLotsToOpen()
   - ✅ UI: OpenLotsCommand (рефакторинг - убрана зависимость от Repository)
   - ✅ EventLoggerListener - использует Symfony Serializer
   - ✅ Configuration: Monolog event_bus channel, Messenger test transport
   - ✅ Integration Tests: 5 тестов, 29 assertions
   - ✅ OpenAPI fixes: PlaceBid Request/Response

4. **Console Command #2:** trade:calculate-winners - ⏳ **СЛЕДУЮЩИЙ ЭТАП**
   - Определение победителей торгов
   - Закрытие лотов по истечению времени

5. **Endpoint #3:** GET /api/v1/trade/lot/{id} (Получить лот)

6. **Endpoint #4:** GET /api/v1/trade/lot (Список лотов)

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

## 💡 Prompt для возобновления работы (после /clear):

```
Мы разрабатываем модуль Trade (сервис торгов) на Symfony 7.4 с CQRS/DDD.

**Текущий статус (2026-06-01):**
- ✅ Endpoint #1 (POST /api/v1/trade/lot) - ЗАВЕРШЕН (реализация + 12 тестов)
- ✅ Endpoint #2 (POST /api/v1/trade/bid) - ЗАВЕРШЕН (реализация + 7 тестов)
- ✅ Console Command #1 (trade:open-lots) - ЗАВЕРШЕН (реализация + рефакторинг + 5 тестов)
  - ✅ Domain: Lot::open(), LotOpenedEvent, DomainEventInterface, LotRepositoryInterface::findLotsToOpen()
  - ✅ Infra: LotRepository::findLotsToOpen() - DQL запрос (status = CREATED AND opensAt <= now)
  - ✅ Application: Open\Command, Open\Handler (публикует LotOpenedEvent)
  - ✅ Application: OpenDueLots\Command, OpenDueLots\Handler, OpenDueLots\Result
  - ✅ Application: EventLoggerListener - использует Symfony Serializer
  - ✅ UI: OpenLotsCommand - рефакторинг (убрана зависимость от Repository)
  - ✅ Configuration: Monolog event_bus канал, Messenger test transport
  - ✅ Integration Tests: 5 тестов, 29 assertions
  - ✅ OpenAPI fixes: PlaceBid Request/Response

**⏳ СЛЕДУЮЩАЯ ЗАДАЧА: Console Command #2 (trade:calculate-winners)**

Определение победителей торгов и закрытие лотов по истечению времени.

**Что нужно сделать:**
1. Реализовать команду `trade:calculate-winners` для закрытия лотов
2. Определить победителей по алгоритму обратного аукциона
3. Реализовать логику финализации ставок
4. Написать интеграционные тесты

**Важные файлы:**
- Команда: `src/Trade/UI/Console/OpenLotsCommand.php`
- Handler: `src/Trade/Application/Lot/Command/OpenDueLots/Handler.php`
- Domain метод: `src/Trade/Domain/Lot/Entity/Lot.php` (метод open())
- Событие: `src/Trade/Domain/Event/LotOpenedEvent.php`
- EventLoggerListener: `src/Trade/Application/Listener/EventLoggerListener.php`
- Тесты: `tests/Test/Integration/Trade/Console/OpenLots/`

**Референсы:**
- Документация: `extra/docs/Trade-Progress.md` (этот файл)
- План разработки: `extra/docs/Trade-Development-Plan.md`
- Требования: `extra/docs/Trade.md`
```

---

## ✅ Console Command #2: trade:calculate-winners (ПОЛНОСТЬЮ ЗАВЕРШЕН)

**Статус:** ✅ **ЗАВЕРШЕН: РЕАЛИЗАЦИЯ + ТЕСТИРОВАНИЕ (2026-06-06)**

**Назначение:** Массовое закрытие лотов по истечению времени и определение победителей торгов (OPEN → CLOSED)

**Что реализовано:**

### 1. Domain Layer ✅

- ✅ **Lot::close(CloseReasonEnum)** (`src/Trade/Domain/Lot/Entity/Lot.php:122`):
  - Доменный метод закрытия лота
  - Валидация: статус должен быть OPEN
  - Переводит статус в CLOSED, устанавливает closeReason

- ✅ **OneToMany связь Lot → Bid** (`src/Trade/Domain/Lot/Entity/Lot.php:68`):
  - Добавлена коллекция `$bids` для eager loading
  - Метод `getBids(): Collection`
  - Обратная связь в Bid: `inversedBy: 'bids'`

- ✅ **LotClosedEvent** (`src/Trade/Domain/Event/LotClosedEvent.php`):
  - Событие закрытия лота с полями: lotId, closeReason, closedAt

- ✅ **WinnerDeterminatedEvent** (`src/Trade/Domain/Event/WinnerDeterminatedEvent.php`):
  - Событие с данными победителей
  - Поля: lotId, winners (массив с bidId, contractorId, allocatedVolume, pricePerTon), determinedAt

### 2. Infra Layer ✅

- ✅ **DTO для маппинга данных** (`src/Trade/Domain/Lot/Repository/`):
  - `LotWithAllocatedBidsDto` - контейнер для лота и его выделенных ставок
  - `AllocatedBidDto` - DTO выделенной ставки (bidId, contractorId, allocatedVolume, pricePerTon)

- ✅ **LotRepository::findLotsToCloseIterator()** (`src/Trade/Infra/Lot/Repository/LotRepository.php:67`):
  - **Батчевая обработка** через yield генератор (batch size = 100)
  - **Нативный SQL через DBAL** для максимальной производительности
  - **Оптимизированная выборка**: только 5 полей (lot.id, bid.id, contractor_id, allocated_volume, price_per_ton)
  - **Фильтрация на уровне SQL**: `b.allocated_volume > 0` в LEFT JOIN
  - **Сортировка победителей**: ORDER BY price_per_ton ASC, created_at ASC
  - Возвращает `Generator<LotWithAllocatedBidsDto>`

### 3. Application Layer ✅

- ✅ **CloseDueLots\Command** (`src/Trade/Application/Lot/Command/CloseDueLots/Command.php`):
  - CQRS команда с параметром `now: DateTimeImmutable`

- ✅ **CloseDueLots\Handler** (`src/Trade/Application/Lot/Command/CloseDueLots/Handler.php`):
  - Итерируется по `findLotsToCloseIterator()`
  - Для каждого лота:
    - Загружает с блокировкой: `lockForUpdate($lotData->lotId)`
    - Вызывает `$lot->close(CloseReasonEnum::EXPIRED)`
    - Публикует `LotClosedEvent`
    - Публикует `WinnerDeterminatedEvent` (если есть ставки)
  - Обрабатывает ошибки с логированием
  - Возвращает статистику: totalProcessed, successfullyClosed, failed

- ✅ **CloseDueLots\Result** (`src/Trade/Application/Lot/Command/CloseDueLots/Result.php`):
  - DTO со статистикой выполнения

### 4. UI Layer (Console) ✅

- ✅ **CalculateWinnersCommand** (`src/Trade/UI/Console/CalculateWinnersCommand.php`):
  - Консольная команда `trade:calculate-winners`
  - Диспатчит `CloseDueLots\Command` через CommandBus
  - Выводит статистику выполнения
  - Exit code: 0 (success), 1 (failures)

**Команда зарегистрирована:** ✅ `php bin/console trade:calculate-winners`

### 5. Оптимизации производительности ✅

**Критические улучшения, внесенные в процессе разработки:**

1. **Фильтрация на уровне SQL вместо PHP**
   - Было: `array_filter($bids, fn($bid) => $bid->getAllocatedVolume() > 0)` - O(n × m)
   - Стало: `LEFT JOIN bid b ON ... AND b.allocated_volume > 0` - O(1)

2. **Минимальная выборка данных**
   - Было: SELECT всех колонок lot и bid (14+ полей)
   - Стало: SELECT только 5 необходимых полей

3. **Нативный SQL через DBAL**
   - Избежание гидратации Doctrine сущностей
   - Прямое маппирование в DTO
   - Батчевая обработка с пагинацией

4. **Типизация через DTO**
   - Было: `yield ['lot' => ..., 'allocatedBids' => [...]]`
   - Стало: `yield new LotWithAllocatedBidsDto(...)`

### 6. Integration Tests ✅

**Директория:** `tests/Test/Integration/Trade/Console/CalculateWinners/`

**Фикстуры созданы:**
- ✅ `CargoTypeFixture.php`, `VolumeStepFixture.php`, `ContractorFixture.php` - справочники
- ✅ `LotFixture.php` - 3 лота (2 просроченных со статусом OPEN, 1 с будущим closesAt)
- ✅ `BidFixture.php` - 5 ставок с allocated_volume для проверки победителей
- ✅ `BaseBidFixture.php` - базовый класс с reflection для установки allocated_volume

**Тесты реализованы (6 сценариев, 73 assertions):**
1. ✅ `testSuccessCloseMultipleLotsWithWinners` - успешное закрытие 2 лотов + проверка статусов + 4 события
2. ✅ `testNoLotsToClose` - нет лотов для закрытия (повторный запуск команды)
3. ✅ `testOnlyExpiredLotsAreClosed` - закрываются только лоты с closes_at <= now (2 из 3)
4. ✅ `testAlreadyClosedLotsAreSkipped` - уже закрытые лоты пропускаются (закрываем 1, запускаем команду)
5. ✅ `testEventsPublished` - проверка LotClosedEvent (2) и WinnerDeterminatedEvent (2) через transport
6. ✅ `testWinnerDataCorrectness` - детальная проверка данных победителей (bidId, contractorId, allocatedVolume, pricePerTon)

**Результат:** ✅ Все тесты проходят успешно (Tests: 6, Assertions: 73)

### 7. Рефакторинг на Carbon::now() ✅

**Проблема:** Необходимость создавать лоты с closesAt в прошлом для тестов, но доменная валидация требует closesAt в будущем.

**Решение:** Рефакторинг всего Trade модуля на использование `Carbon::now()` вместо `new DateTimeImmutable()`.

**Измененные файлы (8 файлов):**
- ✅ `src/Trade/Domain/Lot/Entity/Lot.php` - 6 замен на Carbon::now()
- ✅ `src/Trade/Domain/Lot/Entity/Bid.php` - 5 замен на Carbon::now()
- ✅ `src/Trade/Application/Lot/Command/Open/Handler.php` - Carbon для события
- ✅ `src/Trade/Application/Lot/Command/CloseDueLots/Handler.php` - Carbon для событий
- ✅ `src/Trade/UI/Console/OpenLotsCommand.php` - Carbon::now() для текущего времени
- ✅ `src/Trade/UI/Console/CalculateWinnersCommand.php` - Carbon::now() для текущего времени
- ✅ `src/Trade/Application/Listener/EventLoggerListener.php` - Carbon::now() для логирования
- ✅ `src/Trade/UI/Http/V1/Lot/Create/Action.php` - Carbon::createFromTimestamp()

**Преимущества:**
- ✅ `Carbon::setTestNow()` позволяет мокировать время в тестах
- ✅ Carbon совместим с DateTimeImmutable через `->toDateTimeImmutable()`
- ✅ Более гибкая работа со временем в тестах (избежание reflection)
- ✅ Чистое решение без нарушения доменных инвариантов

**Логика тестирования с Carbon:**
```php
// setUp(): Мокаем время на 3 дня назад
Carbon::setTestNow(Carbon::now()->subDays(3));

// Фикстуры создают лоты с closesAt = Carbon::now() + 1-2 дня
// Относительно мокнутого времени это валидные даты в будущем

// setUp(): Возвращаем реальное время
Carbon::setTestNow();

// Теперь лоты с closesAt оказываются в прошлом (просрочены)
```

---

---

## ✅ Endpoint #3: GET /api/v1/trade/lot/{lotId} (ПОЛНОСТЬЮ ЗАВЕРШЕН)

**Статус:** ✅ **ЗАВЕРШЕН: РЕАЛИЗАЦИЯ + ТЕСТИРОВАНИЕ (2026-06-07)**

**Назначение:** Получение детальной информации о лоте с массивом ID победителей

**Что реализовано:**

### 1. Domain Layer ✅

- ✅ **LotDetailsDto** (`src/Trade/Domain/Lot/Repository/LotDetailsDto.php`):
  - DTO для передачи данных лота из репозитория в Application слой
  - Поля: lotId, status, totalVolume, startPrice, priceStep, opensAt, closesAt, closeReason, winnerContractorIds
  - Все ID объекты доменного типа `Id`

- ✅ **LotRepositoryInterface::getLotDetails()** (`src/Trade/Domain/Lot/Repository/LotRepositoryInterface.php`):
  - Метод получения деталей лота с ID победителей

### 2. Infra Layer ✅

- ✅ **LotRepository::getLotDetails()** (`src/Trade/Infra/Lot/Repository/LotRepository.php:142`):
  - **Нативный SQL через DBAL** с JOIN на bid
  - Выборка базовой информации лота + массив contractor_id победителей
  - Фильтр: `b.allocated_volume > 0` - только выделенные ставки
  - Сортировка победителей: price_per_ton ASC, created_at ASC
  - Маппинг результата в `LotDetailsDto`
  - Выбрасывает `NotFoundException` если лот не найден

### 3. Application Layer ✅

- ✅ **Query** (`src/Trade/Application/Lot/Query/Get/Query.php`):
  - CQRS query с параметром `lotId: Id`

- ✅ **Result** (`src/Trade/Application/Lot/Query/Get/Result.php`):
  - Application DTO с конвертацией `Id` → `string`
  - Поля: lotId, status, totalVolume, startPrice, priceStep, opensAt, closesAt, closeReason, winnerContractorIds

- ✅ **Handler** (`src/Trade/Application/Lot/Query/Get/Handler.php`):
  - Реализует QueryHandlerInterface
  - Вызывает `getLotDetails()` из репозитория
  - Маппинг `LotDetailsDto` → `Result` (конвертация Id objects в strings)

### 4. UI Layer (HTTP API) ✅

- ✅ **Response** (`src/Trade/UI/Http/V1/Lot/Get/Response.php`):
  - Реализует ResponseInterface
  - **UTC timestamps** (int): opensAt, closesAt
  - OpenAPI аннотации с примерами
  - Поле `winnerContractorIds: array<string>` - пустой массив если лот не закрыт

- ✅ **Action** (`src/Trade/UI/Http/V1/Lot/Get/Action.php`):
  - Route: `GET /api/v1/trade/lot/{lotId}` (name: `trade_get-lot`)
  - OpenAPI документация (tag: `Trade - Лоты`)
  - **UUID валидация**: `Uuid::isValid()` с возвратом HTTP 400
  - Конвертация DateTimeImmutable → Unix timestamp UTC
  - Маппинг Result → Response
  - Обёртка ResponseWrapper

### 5. Integration Tests ✅

**Директория:** `tests/Test/Integration/Trade/Api/GetLot/`

**Фикстуры созданы:**
- ✅ `CargoTypeFixture.php`, `VolumeStepFixture.php`, `ContractorFixture.php`
- ✅ `LotFixture.php` - 2 лота (один OPEN, один CLOSED с победителями)
- ✅ `BidFixture.php` - 5 ставок (2 с allocated_volume для победителей, 3 вытесненные с allocated_volume = 0)

**Тесты реализованы (4 сценария, 33 assertions):**
1. ✅ `testSuccessGetOpenLot` - получение открытого лота (пустой массив победителей)
2. ✅ `testSuccessGetClosedLotWithWinners` - получение закрытого лота с 2 победителями
3. ✅ `testFailedLotNotFound` - несуществующий лот (HTTP 404)
4. ✅ `testFailedInvalidUuid` - невалидный UUID (HTTP 400)

**Результат:** ✅ Все тесты проходят успешно (Tests: 4, Assertions: 33)

### 6. Bug Fixes ✅

- ✅ Исправлен конфликт импортов в Action.php (удален import `Symfony\Component\HttpFoundation\Response`)
- ✅ Добавлена UUID валидация с `BadRequestHttpException` для HTTP 400

---

## ✅ Оптимизация N+1 запросов в CloseDueLots (ЗАВЕРШЕНА)

**Статус:** ✅ **ЗАВЕРШЕН (2026-06-07)**

**Проблема:** N+1 запросов при вызове `lockForUpdate($lotId)` внутри цикла в `CloseDueLots/Handler.php`

**Решение:** Рефакторинг `findLotsToCloseIterator()` на 3-шаговую батчевую загрузку

### Изменения ✅

**1. LotWithAllocatedBidsDto обновлен:**
- ✅ Было: `public Id $lotId`
- ✅ Стало: `public Lot $lot` - сущность лота вместо ID
- ✅ Удалены лишние импорты

**2. LotRepository::findLotsToCloseIterator() переписан:**
- ✅ **Шаг 1**: SELECT lot IDs с `FOR UPDATE` (блокировка строк в PostgreSQL)
  ```sql
  SELECT l.id FROM trade.lot l
  WHERE l.status = :status AND l.termination_closes_at <= :now
  ORDER BY l.id
  LIMIT :limit OFFSET :offset
  FOR UPDATE
  ```
- ✅ **Шаг 2**: Загрузка Lot entities через Doctrine QueryBuilder по заблокированным ID
  ```php
  $lots = $lotQueryBuilder->select('l')
      ->from(Lot::class, 'l')
      ->where($lotQueryBuilder->expr()->in('l.id', ':ids'))
      ->setParameter('ids', $lotIds)
      ->getQuery()
      ->getResult();
  ```
- ✅ **Шаг 3**: Загрузка allocated bids одним IN-запросом
  ```sql
  SELECT bid.* FROM trade.bid b
  WHERE b.lot_id IN (:lot_ids) AND b.allocated_volume > 0
  ORDER BY b.lot_id, b.price_per_ton ASC, b.created_at ASC
  ```
- ✅ Индексирование лотов по ID для O(1) доступа: `$lotsById[$lot->getId()->value] = $lot`
- ✅ Группировка ставок по lot_id
- ✅ Yield `LotWithAllocatedBidsDto` с предзагруженной сущностью Lot

**3. CloseDueLots/Handler.php упрощен:**
- ✅ Было: `$lot = $this->lotRepository->lockForUpdate($lotData->lotId);` - дополнительный запрос
- ✅ Стало: `$lot = $lotData->lot;` - лот уже загружен и заблокирован

**4. Улучшение именования:**
- ✅ Было: `$qb1`, `$qb2` - плохая практика в PHP
- ✅ Стало: `$lotQueryBuilder` - понятное имя

### Результат оптимизации ✅

- ✅ **Было**: N+1 запросов (1 для выборки ID + N вызовов lockForUpdate)
- ✅ **Стало**: 3 запроса на батч (независимо от размера батча 100 лотов):
  1. SELECT IDs с FOR UPDATE
  2. SELECT Lot entities по IDs
  3. SELECT allocated bids по lot_ids
- ✅ Производительность: O(3) вместо O(N+1)
- ✅ Все тесты проходят: 35 тестов, 231 assertions

---

## ✅ Обновление конфигурации Swagger UI (ЗАВЕРШЕНО)

**Статус:** ✅ **ЗАВЕРШЕН (2026-06-07)**

**Задача:** Заменить форму Bearer token на header-based аутентификацию через `x-user-id`

### Изменения ✅

**Файл:** `config/packages/nelmio_api_doc.yaml`

- ✅ **Было**:
  ```yaml
  securitySchemes:
      Bearer:
          type: http
          scheme: bearer
  security:
      - Bearer: []
  ```

- ✅ **Стало**:
  ```yaml
  securitySchemes:
      UserIdHeader:
          type: apiKey
          in: header
          name: x-user-id
          description: 'UUID пользователя от имени которого выполняется запрос'
  security:
      - UserIdHeader: []
  ```

### Результат ✅

- ✅ В Swagger UI (http://localhost:8088/api/doc) кнопка "Authorize" позволяет указать UUID пользователя
- ✅ UUID автоматически подставляется в заголовок `x-user-id` для всех запросов
- ✅ Соответствует архитектуре проекта (внешний Gateway управляет авторизацией)

---

## ✅ Обновление документации (ЗАВЕРШЕНО)

**Статус:** ✅ **ЗАВЕРШЕН (2026-06-07)**

### README.md актуализирован ✅

- ✅ Добавлен модуль `Trade` в список текущих модулей
- ✅ Упомянут заголовок `x-user-id` в разделе авторизации
- ✅ Добавлена схема `trade` в организацию БД
- ✅ Создан новый большой раздел "Модуль Trade - Торговая система" с описанием:
  - HTTP endpoints (POST /lot, GET /lot/{id}, POST /bid)
  - Console commands (trade:open-lots, trade:calculate-winners)
  - Бизнес-логики (Лот, Ставка, Расчёт победителей)
  - Структуры модуля
  - Оптимизаций производительности (батчинг, N+1 elimination)

### Claude.md актуализирован ✅

- ✅ Упомянута конфигурация `x-user-id` в Swagger UI
- ✅ Расширен раздел "Trade Module Structure" с endpoints и командами
- ✅ Добавлен раздел "Trade Module Features" с описанием:
  - Бизнес-логики (reverse auction, lifecycle, locking)
  - Ключевых endpoints
  - Console команд с батчевой обработкой
  - Оптимизации производительности (3-step batch loading)

---

## 📊 Финальная статистика модуля Trade

**Общее количество тестов:** 35 (24 API + 11 Console)
**Общее количество assertions:** 231

### Разбивка по компонентам:

1. **CreateLot API** - 12 тестов, 45 assertions
2. **PlaceBid API** - 7 тестов, 56 assertions
3. **GetLot API** - 4 теста, 33 assertions
4. **OpenLots Console** - 5 тестов, 29 assertions
5. **CalculateWinners Console** - 6 тестов, 73 assertions

**Покрытие функциональности:**
- ✅ Создание лотов с валидацией (12 сценариев)
- ✅ Размещение ставок с вытеснением (7 сценариев)
- ✅ Получение лота с победителями (4 сценария)
- ✅ Автоматическое открытие лотов (5 сценариев)
- ✅ Закрытие лотов и определение победителей (6 сценариев)

---

## 🎯 Итоговый статус модуля Trade

### Реализованные функции ✅

1. ✅ **POST /api/v1/trade/lot** - Создание лота
2. ✅ **POST /api/v1/trade/bid** - Размещение ставки
3. ✅ **GET /api/v1/trade/lot/{lotId}** - Получение лота с победителями
4. ✅ **trade:open-lots** - Автоматическое открытие лотов
5. ✅ **trade:calculate-winners** - Закрытие лотов и расчёт победителей

### Архитектурные улучшения ✅

- ✅ Четырехслойная архитектура (UI → Application → Domain ← Infra)
- ✅ CQRS с тремя шинами (command.bus, query.bus, event.bus)
- ✅ Доменные события (LotOpenedEvent, LotClosedEvent, WinnerDeterminatedEvent)
- ✅ Strategy Pattern для алгоритма размещения ставок
- ✅ Пессимистические блокировки для конкурентного доступа
- ✅ Batch processing с оптимизацией N+1 запросов
- ✅ DTO для разделения слоев (Domain → Application → UI)
- ✅ UUID валидация с корректными HTTP статусами

### Производительность ✅

- ✅ Батчевая обработка лотов (100 шт за раз)
- ✅ 3-step batch loading: O(3) вместо O(N+1)
- ✅ Нативный SQL через DBAL для сложных выборок
- ✅ Фильтрация на уровне SQL вместо PHP
- ✅ Минимальная выборка данных (только нужные поля)
- ✅ Индексация для O(1) доступа в памяти

### Качество кода ✅

- ✅ 35 интеграционных тестов (231 assertion)
- ✅ PSR-12 code style
- ✅ Строгая типизация (strict_types=1)
- ✅ OpenAPI документация для всех endpoints
- ✅ Event logging в JSON формате (var/log/events.log)
- ✅ Carbon для мокирования времени в тестах

---

**Дата создания:** 2026-04-26
**Дата последнего обновления:** 2026-06-07 (модуль Trade полностью завершен: 3 endpoints, 2 console команды, оптимизация N+1, обновление документации)
