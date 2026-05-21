# Trade Module - Progress Tracker

## 📍 Текущий статус разработки

**Дата последнего обновления:** 2026-05-21
**Текущий этап:** Реорганизация структуры завершена. Начинаем реализацию Endpoint #1 (Domain Layer)

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
   - ✅ В `config/packages/doctrine.yaml` закомментирован mapping для `SomeModule`

6. **Документация**
   - ✅ `CLAUDE.md` обновлен с новой структурой Trade модуля

---

## ❌ Что НЕ реализовано

### Endpoint #1: POST /api/v1/trade/lot (NOT STARTED)

**Статус:** ❌ НЕ РЕАЛИЗОВАН в текущей ветке

#### Что нужно реализовать:

**1. Domain Layer (Lot Aggregate)**

- **Enums:**
  - `Trade\Domain\Lot\Enum\LotStatusEnum` (CREATED, OPEN, CLOSED)
  - `Trade\Domain\Lot\Enum\CloseReasonEnum` (EXPIRED, MANUAL)

- **Value Objects (Embeddables):**
  - `Trade\Domain\Lot\ValueObject\Volume` - управление объёмом (total, reserved, free)
    - **Инварианты:** максимум 100,000 тонн, кратность volumeStep
  - `Trade\Domain\Lot\ValueObject\Price` - цены (start, step)
  - `Trade\Domain\Lot\ValueObject\LotTermination` - завершение (closesAt, closeReason)

- **Entity:**
  - `Trade\Domain\Lot\Entity\Lot` - агрегат лота
    - Конструктор с валидацией дат
    - Методы: `getId()`, `getStatus()`, `canAcceptBids()`, `getFreeVolume()`

- **Repository Interface:**
  - `Trade\Domain\Lot\Repository\LotRepositoryInterface`
    - `add(Lot): void`
    - `get(Id): Lot`

**2. Infra Layer**

- **Repository Implementation:**
  - `Trade\Infra\Lot\Repository\LotRepository`

**3. Application Layer**

- **Command:**
  - `Trade\Application\Lot\Command\Create\Command` - DTO с параметрами (camelCase)

- **Result:**
  - `Trade\Application\Lot\Command\Create\Result` - возвращаемый DTO (lotId, status)

- **Handler:**
  - `Trade\Application\Lot\Command\Create\Handler`
  - Валидирует существование CargoType и VolumeStep
  - Создаёт Lot в статусе CREATED
  - Возвращает Result (не доменную сущность!)

**4. UI Layer (HTTP API)**

- **Request:**
  - `Trade\UI\Http\V1\Lot\Create\Request`
  - Валидации: Uuid, Positive, NotBlank
  - OpenAPI аннотации
  - camelCase properties
  - Unix timestamp (int) для opensAt/closesAt

- **Response:**
  - `Trade\UI\Http\V1\Lot\Create\Response`
  - Структура: `{lotId, status}`

- **Action:**
  - `Trade\UI\Http\V1\Lot\Create\Action`
  - Route: `POST /api/v1/trade/lot`
  - OpenAPI документация (tags: Trade - Lots)
  - Маппинг Result → Response

**5. Database Migration**

- **Нужно создать миграцию для таблицы `trade.lot`:**
  - id (UUID, PK)
  - cargo_type_id (FK → cargo_type)
  - total_volume, reserved_volume (Embeddable Volume)
  - start_price, price_step (Embeddable Price)
  - status (enum)
  - opens_at, closes_at, close_reason (Embeddable LotTermination)
  - volume_step_id (FK → volume_step)
  - created_at, updated_at
  - Индексы: `idx_lot_status`, `idx_lot_opens_at`, `idx_lot_closes_at`

**6. Тестирование**

- **Нужно создать фикстуры** (используя seed данные из миграций)
- **Нужно создать интеграционные тесты** для всех сценариев валидации

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

1. **Endpoint #1:** POST /api/v1/trade/lot (Создание лота) - **ТЕКУЩАЯ ЗАДАЧА**
   - Реализовать Domain Layer (Lot entity, Value Objects, Enums)
   - Реализовать Application Layer (Command/Handler/Result)
   - Реализовать UI Layer (Request/Response/Action)
   - Создать миграцию для таблицы `trade.lot`
   - Написать интеграционные тесты

2. **Endpoint #2:** POST /api/v1/trade/bid (Размещение ставки)
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
- ✅ Структура модуля Trade реорганизована (2026-05-21):
  - Trade/Domain/Dictionary/ - справочники
  - Trade/Domain/Lot/ - агрегат Lot (готов для разработки)
  - Trade/Infra/Dictionary/ и Trade/Infra/Lot/
  - Все namespace обновлены
- ✅ CLAUDE.md обновлен с новой структурой
- ⏸️ **ОСТАНОВИЛИСЬ НА:** Начало реализации Endpoint #1 - Domain Layer

**Следующий шаг:**
Начать реализацию Endpoint #1: POST /api/v1/trade/lot (создание лота)

**Порядок разработки:**
1. **Domain Layer** (ТЕКУЩИЙ ШАГ):
   - Создать Enums: `LotStatusEnum`, `CloseReasonEnum` в `Trade/Domain/Lot/Enum/`
   - Создать Value Objects в `Trade/Domain/Lot/ValueObject/`:
     - `Volume` (total, reserved, volumeStep) - с инвариантами
     - `Price` (startPrice, priceStep)
     - `LotTermination` (closesAt, closeReason)
   - Создать `Lot` entity в `Trade/Domain/Lot/Entity/Lot.php`
   - Создать `LotRepositoryInterface` в `Trade/Domain/Lot/Repository/`

2. **Application Layer**: Command/Handler/Result
3. **UI Layer**: Request/Response/Action
4. **Infra Layer**: LotRepository implementation
5. **Migration**: Сгенерировать миграцию (code-first подход)
6. **Validation**: doctrine:schema:validate

**Архитектурные требования:**
- CQRS: Command/Handler/Result
- Domain-first подход
- Value Objects для Volume, Price, LotTermination
- Unix timestamp (int) для дат в Request DTO
- Handler возвращает Result (Application DTO), а не доменную сущность
- LotRepository должен наследоваться от ServiceEntityRepository

**Референсный код:**
- Пример структуры: `src/SomeModule/UI/Http/V1/Category/Create/`
- Пример Entity: `src/SomeModule/Domain/Post/Entity/Category.php`

**Важно:**
- Использовать CoreKit\Domain\Entity\Id для UUID
- camelCase для PHP properties, snake_case для БД
- Первичная валидация в Request DTO, вторичная в конструкторе Lot
- После реализации логики - ОСТАНОВИТЬСЯ для ревью
- Тесты пишем только после успешного ревью

См. детали в:
- extra/docs/Trade-Progress.md (этот файл)
- extra/docs/Trade-Development-Plan.md (раздел Endpoint #1)
- extra/docs/Trade.md (требования к лоту)
- extra/docs/Auction-Algorithm-Implementation.md (тактический DDD)
```

---

**Дата создания:** 2026-04-26
**Дата последнего обновления:** 2026-05-21 (реорганизация структуры)
