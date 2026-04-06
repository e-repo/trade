# План разработки Trade Service (Сервис торгов)

## Обзор проекта

Реализация сервиса торгов с механизмом конкурентного резервирования объёма перевозки на базе Symfony 7.4 с использованием CQRS/DDD паттернов.

### Ключевые особенности архитектуры

1. **Аутентификация вынесена за границы сервиса**
   - API Gateway отвечает за аутентификацию пользователей
   - В сервис приходит заголовок `x-user-id` с идентификатором инициатора запроса
   - Никакой дополнительной проверки авторизации внутри сервиса не требуется

2. **Event Bus для интеграции с Realtime Service**
   - События лотов публикуются в Kafka топики
   - Realtime-service подписывается на топики и рассылает обновления по WebSocket
   - **На первом этапе**: вывод событий в лог (через Symfony Messenger + Monolog handler)
   - **В будущем**: интеграция с Kafka

3. **Конкурентное резервирование**
   - Пессимистические блокировки (`SELECT ... FOR UPDATE`)
   - Retry механизм для обработки deadlock/serialization failures
   - Транзакционность через `command.bus` middleware

4. **Тактический DDD с паттерном Strategy**
   - Бизнес-операция `Lot::placeBid()` — центральная точка размещения ставок
   - `BidCollection` — Value Object для работы с коллекциями ставок
   - `BidAllocationStrategyInterface` — стратегия распределения объёма
   - `Bid` самомодифицируется через методы `allocate()`, `displace()`, `reject()`

---

## Подход к разработке: Endpoint-Driven Development

Вместо разработки по слоям (Domain → Application → UI), ведём разработку **вертикальными срезами** — по одному endpoint за раз, включая все слои сразу:

```
Endpoint → UI Action → Command/Query → Domain Logic → Infra → Migration → Test
```

**Преимущества:**
- ✅ Быстрый feedback — каждый endpoint можно сразу протестировать
- ✅ Чёткий прогресс — видно, что уже работает
- ✅ Минимум over-engineering — реализуем только то, что нужно
- ✅ Тактический DDD — бизнес-логика инкапсулирована в агрегатах

---

## Порядок разработки

### Подготовительный этап (выполняется один раз)

**Цель:** Создать инфраструктуру модуля Trade и справочные данные

#### Задачи:

1. **Создание структуры модуля Trade**
   - [ ] Создать директории:
     - `src/Trade/UI/Http/V1/` - HTTP endpoints
     - `src/Trade/UI/Console/` - Console commands
     - `src/Trade/Application/` - Commands, Queries, Listeners
     - `src/Trade/Domain/` - Entities, Repository Interfaces, Value Objects
     - `src/Trade/Infra/` - Repositories, Fetchers

2. **Регистрация модуля в проекте**
   - [ ] Добавить namespace `Trade\` в `composer.json`:
     ```json
     "autoload": {
         "psr-4": {
             "Trade\\": "src/Trade",
             "SomeModule\\": "src/SomeModule",
             "CoreKit\\": "src/CoreKit"
         }
     }
     ```
   - [ ] Зарегистрировать сервисы в `config/services.yaml`:
     ```yaml
     Trade\:
         resource: '../src/Trade'
         exclude:
             - '../src/Trade/{Kernel.php,*Dto.php}'
             - '../src/Trade/{Entity,Enum,Event,ValueObject}'
     ```
   - [ ] Выполнить `composer dump-autoload`

3. **Создание справочных сущностей (Domain)**

   **Расположение:** `src/Trade/Domain/Entity/`

   - [ ] **CargoType** (тип груза)
     ```php
     #[ORM\Entity(repositoryClass: CargoTypeRepository::class)]
     #[ORM\Table(schema: 'trade')]
     class CargoType
     {
         #[ORM\Id]
         #[ORM\Column(type: 'uuid')]
         private Id $id;

         #[ORM\Column(length: 100, unique: true)]
         private string $name;

         #[ORM\Column]
         private DateTimeImmutable $createdAt;

         #[ORM\Column(nullable: true)]
         private ?DateTimeImmutable $updatedAt;
     }
     ```

   - [ ] **VolumeStep** (грузоподъёмность машины)
     ```php
     #[ORM\Entity(repositoryClass: VolumeStepRepository::class)]
     #[ORM\Table(schema: 'trade')]
     class VolumeStep
     {
         #[ORM\Id]
         #[ORM\Column(type: 'uuid')]
         private Id $id;

         #[ORM\Column(length: 50, unique: true)]
         private string $name; // "20 тонн"

         #[ORM\Column]
         private int $value; // 20

         #[ORM\Column]
         private DateTimeImmutable $createdAt;
     }
     ```

   - [ ] **Contractor** (подрядчик)
     ```php
     #[ORM\Entity(repositoryClass: ContractorRepository::class)]
     #[ORM\Table(schema: 'trade')]
     class Contractor
     {
         #[ORM\Id]
         #[ORM\Column(type: 'uuid')]
         private Id $id;

         #[ORM\Column(length: 255, unique: true)]
         private string $email;

         #[ORM\Column(length: 100)]
         private string $firstName;

         #[ORM\Column(length: 100)]
         private string $secondName;

         #[ORM\Column(length: 100, nullable: true)]
         private ?string $patronymic;

         #[ORM\Column(type: 'uuid')]
         private Id $agreementId;

         #[ORM\Column]
         private DateTimeImmutable $createdAt;

         #[ORM\Column(nullable: true)]
         private ?DateTimeImmutable $updatedAt;
     }
     ```

4. **Создание Repository Interfaces для справочников**
   - [ ] `CargoTypeRepositoryInterface` → `get(Id): CargoType`
   - [ ] `VolumeStepRepositoryInterface` → `get(Id): VolumeStep`
   - [ ] `ContractorRepositoryInterface` → `get(Id): Contractor`

5. **Создание Repository Implementations (Infra)**

   **Расположение:** `src/Trade/Infra/Entity/Repository/`

   - [ ] `CargoTypeRepository`
   - [ ] `VolumeStepRepository`
   - [ ] `ContractorRepository`

6. **Database Migrations для справочников**

   **Migration #1:** Создание схемы и таблиц справочников
   ```sql
   -- migrations/Trade/Version20260405120001.php

   CREATE SCHEMA IF NOT EXISTS trade;
   SET search_path TO trade;

   CREATE TABLE cargo_type (
       id UUID PRIMARY KEY,
       name VARCHAR(100) UNIQUE NOT NULL,
       created_at TIMESTAMP NOT NULL,
       updated_at TIMESTAMP
   );

   CREATE TABLE volume_step (
       id UUID PRIMARY KEY,
       name VARCHAR(50) UNIQUE NOT NULL,
       value INT NOT NULL,
       created_at TIMESTAMP NOT NULL
   );

   CREATE TABLE contractor (
       id UUID PRIMARY KEY,
       email VARCHAR(255) UNIQUE NOT NULL,
       first_name VARCHAR(100) NOT NULL,
       second_name VARCHAR(100) NOT NULL,
       patronymic VARCHAR(100),
       agreement_id UUID NOT NULL,
       created_at TIMESTAMP NOT NULL,
       updated_at TIMESTAMP
   );

   CREATE INDEX idx_contractor_email ON contractor(email);
   ```

   **Migration #2:** Seed данных в справочники
   ```sql
   -- migrations/Trade/Version20260405120002.php

   SET search_path TO trade;

   -- Seed CargoType
   INSERT INTO cargo_type (id, name, created_at) VALUES
   ('550e8400-e29b-41d4-a716-446655440001', 'Семена подсолнечника', NOW()),
   ('550e8400-e29b-41d4-a716-446655440002', 'Пшеница', NOW()),
   ('550e8400-e29b-41d4-a716-446655440003', 'Кукуруза', NOW());

   -- Seed VolumeStep
   INSERT INTO volume_step (id, name, value, created_at) VALUES
   ('550e8400-e29b-41d4-a716-446655440010', '20 тонн', 20, NOW()),
   ('550e8400-e29b-41d4-a716-446655440011', '40 тонн', 40, NOW()),
   ('550e8400-e29b-41d4-a716-446655440012', '60 тонн', 60, NOW());

   -- Seed Contractor (тестовые данные для dev)
   INSERT INTO contractor (id, email, first_name, second_name, patronymic, agreement_id, created_at) VALUES
   ('550e8400-e29b-41d4-a716-446655440020', 'contractor1@example.com', 'Иван', 'Иванов', 'Иванович', '550e8400-e29b-41d4-a716-446655440100', NOW()),
   ('550e8400-e29b-41d4-a716-446655440021', 'contractor2@example.com', 'Пётр', 'Петров', 'Петрович', '550e8400-e29b-41d4-a716-446655440101', NOW());
   ```

**Результат подготовительного этапа:**
- ✅ Модуль Trade зарегистрирован
- ✅ Справочники созданы и заполнены тестовыми данными
- ✅ Готова база для разработки endpoint'ов

---

### Endpoint #1: POST /api/v1/trade/lot (Создание лота)

**Цель:** Реализовать создание лота через HTTP API

#### Что реализуем:

**1. Domain: Enums**

**Расположение:** `src/Trade/Domain/Enum/`

- [ ] **LotStatusEnum**
  ```php
  enum LotStatusEnum: string
  {
      case CREATED = 'CREATED';
      case OPEN = 'OPEN';
      case CLOSED = 'CLOSED';
  }
  ```

- [ ] **CloseReasonEnum**
  ```php
  enum CloseReasonEnum: string
  {
      case EXPIRED = 'EXPIRED';
      case MANUAL = 'MANUAL';
  }
  ```

**2. Domain: Value Objects**

**Расположение:** `src/Trade/Domain/ValueObject/`

- [ ] **Volume** (Embeddable)
  ```php
  #[ORM\Embeddable]
  class Volume
  {
      #[ORM\Column]
      private int $totalVolume;

      #[ORM\Column]
      private int $reservedVolume = 0;

      public function getFreeVolume(): int
      {
          return $this->totalVolume - $this->reservedVolume;
      }

      public function reserve(int $amount): void
      {
          if ($this->reservedVolume + $amount > $this->totalVolume) {
              throw new DomainException('Cannot reserve more than total volume');
          }
          $this->reservedVolume += $amount;
      }
  }
  ```

- [ ] **Price** (Embeddable)
  ```php
  #[ORM\Embeddable]
  class Price
  {
      #[ORM\Column]
      private int $startPrice; // в копейках

      #[ORM\Column]
      private int $priceStep;
  }
  ```

- [ ] **LotTermination** (Embeddable)
  ```php
  #[ORM\Embeddable]
  class LotTermination
  {
      #[ORM\Column]
      private DateTimeImmutable $closesAt;

      #[ORM\Column(enumType: CloseReasonEnum::class, nullable: true)]
      private ?CloseReasonEnum $closeReason = null;
  }
  ```

**3. Domain: Lot Entity (Aggregate Root)**

**Расположение:** `src/Trade/Domain/Lot/Entity/Lot.php`

- [ ] Создать сущность Lot (минимальная версия для создания)
  ```php
  #[ORM\Entity(repositoryClass: LotRepository::class)]
  #[ORM\Table(schema: 'trade')]
  class Lot
  {
      #[ORM\Id]
      #[ORM\Column(type: 'uuid')]
      private Id $id;

      #[ORM\ManyToOne(targetEntity: CargoType::class)]
      #[ORM\JoinColumn(nullable: false)]
      private CargoType $cargoType;

      #[ORM\Embedded(class: Volume::class)]
      private Volume $volume;

      #[ORM\Embedded(class: Price::class)]
      private Price $price;

      #[ORM\Column(enumType: LotStatusEnum::class)]
      private LotStatusEnum $status;

      #[ORM\Column]
      private DateTimeImmutable $opensAt;

      #[ORM\Embedded(class: LotTermination::class)]
      private LotTermination $termination;

      #[ORM\ManyToOne(targetEntity: VolumeStep::class)]
      #[ORM\JoinColumn(nullable: false)]
      private VolumeStep $volumeStep;

      #[ORM\Column]
      private int $version = 1;

      #[ORM\Column]
      private DateTimeImmutable $createdAt;

      #[ORM\Column(nullable: true)]
      private ?DateTimeImmutable $updatedAt = null;

      public function __construct(/* DTO */) { /* ... */ }

      public function canAcceptBids(): bool
      {
          return $this->status === LotStatusEnum::OPEN
              && new DateTimeImmutable() <= $this->termination->getClosesAt();
      }

      // Другие методы добавим в следующих endpoint'ах
  }
  ```

**4. Domain: Repository Interface**

**Расположение:** `src/Trade/Domain/Lot/Repository/LotRepositoryInterface.php`

- [ ] Создать интерфейс
  ```php
  interface LotRepositoryInterface
  {
      public function add(Lot $lot): void;
      public function get(Id $id): Lot;
  }
  ```

**5. Infra: Repository Implementation**

**Расположение:** `src/Trade/Infra/Lot/Repository/LotRepository.php`

- [ ] Реализовать `LotRepositoryInterface`
  ```php
  final class LotRepository implements LotRepositoryInterface
  {
      public function __construct(
          private EntityManagerInterface $em
      ) {}

      public function add(Lot $lot): void
      {
          $this->em->persist($lot);
      }

      public function get(Id $id): Lot
      {
          $lot = $this->em->find(Lot::class, $id);

          if ($lot === null) {
              throw new NotFoundException('Lot not found');
          }

          return $lot;
      }
  }
  ```

**6. Application: Command + Handler**

**Расположение:** `src/Trade/Application/Lot/Command/Create/`

- [ ] **Command.php**
  ```php
  final readonly class Command
  {
      public function __construct(
          public string $cargoTypeId,
          public int $totalVolume,
          public int $startPrice,
          public int $priceStep,
          public string $volumeStepId,
          public DateTimeImmutable $opensAt,
          public DateTimeImmutable $closesAt,
      ) {}
  }
  ```

- [ ] **Handler.php**
  ```php
  final readonly class Handler implements CommandHandlerInterface
  {
      public function __construct(
          private LotRepositoryInterface $lotRepository,
          private CargoTypeRepositoryInterface $cargoTypeRepository,
          private VolumeStepRepositoryInterface $volumeStepRepository,
      ) {}

      public function __invoke(Command $command): void
      {
          $cargoType = $this->cargoTypeRepository->get(new Id($command->cargoTypeId));
          $volumeStep = $this->volumeStepRepository->get(new Id($command->volumeStepId));

          $lot = new Lot(/* DTO с параметрами */);

          $this->lotRepository->add($lot);
      }
  }
  ```

**7. UI: HTTP Action**

**Расположение:** `src/Trade/UI/Http/V1/Lot/Create/`

- [ ] **Request.php** (DTO с валидациями)
  ```php
  final readonly class Request
  {
      public function __construct(
          #[Assert\Uuid]
          public string $cargo_type_id,

          #[Assert\Positive]
          public int $total_volume,

          #[Assert\Positive]
          public int $start_price,

          #[Assert\Positive]
          public int $price_step,

          #[Assert\Uuid]
          public string $volume_step_id,

          public DateTimeImmutable $opens_at,
          public DateTimeImmutable $closes_at,
      ) {}
  }
  ```

- [ ] **Response.php**
  ```php
  final readonly class Response
  {
      public function __construct(
          public string $lot_id,
          public string $status,
      ) {}
  }
  ```

- [ ] **Action.php**
  ```php
  #[Route('/api/v1/trade/lot', methods: ['POST'])]
  #[OA\Post(summary: 'Create lot', tags: ['Trade - Lots'])]
  final class Action extends AbstractController
  {
      public function __construct(
          private readonly CommandBusInterface $commandBus,
      ) {}

      public function __invoke(Request $request): JsonResponse
      {
          $this->commandBus->dispatch(
              new Command(/* map from request */)
          );

          return new JsonResponse(new ResponseWrapper(
              data: new Response(/* ... */)
          ));
      }
  }
  ```

**8. Migration: Таблица lot**

- [ ] Создать миграцию
  ```sql
  SET search_path TO trade;

  CREATE TABLE lot (
      id UUID PRIMARY KEY,
      cargo_type_id UUID NOT NULL REFERENCES cargo_type(id),
      total_volume INT NOT NULL CHECK (total_volume > 0),
      reserved_volume INT NOT NULL DEFAULT 0 CHECK (reserved_volume >= 0 AND reserved_volume <= total_volume),
      start_price INT NOT NULL CHECK (start_price > 0),
      price_step INT NOT NULL CHECK (price_step > 0),
      status VARCHAR(20) NOT NULL,
      opens_at TIMESTAMP NOT NULL,
      closes_at TIMESTAMP NOT NULL CHECK (closes_at > opens_at),
      close_reason VARCHAR(20),
      volume_step_id UUID NOT NULL REFERENCES volume_step(id),
      version INT NOT NULL DEFAULT 1,
      created_at TIMESTAMP NOT NULL,
      updated_at TIMESTAMP
  );

  CREATE INDEX idx_lot_status ON lot(status);
  CREATE INDEX idx_lot_opens_at ON lot(opens_at);
  CREATE INDEX idx_lot_closes_at ON lot(closes_at);
  ```

**9. Test: Integration Test**

**Расположение:** `tests/Test/Integration/Trade/Lot/CreateLotTest.php`

- [ ] Тест успешного создания
- [ ] Тест с невалидными данными
- [ ] Тест с несуществующим cargo_type_id

**Результат Endpoint #1:**
- ✅ Можно создать лот через POST /api/v1/trade/lot
- ✅ Лот сохраняется в БД в статусе CREATED
- ✅ Валидации работают

---

### Endpoint #2: POST /api/v1/trade/bid (Размещение ставки)

**Цель:** Реализовать размещение ставки с конкурентным распределением объёма (тактический DDD)

#### Что реализуем:

**1. Domain: Enum BidStatusEnum**

**Расположение:** `src/Trade/Domain/Enum/BidStatusEnum.php`

- [ ] Создать enum
  ```php
  enum BidStatusEnum: string
  {
      case PENDING = 'PENDING';
      case ACTIVE = 'ACTIVE';
      case PARTIALLY_ACTIVE = 'PARTIALLY_ACTIVE';
      case OUTBID = 'OUTBID';
      case REJECTED = 'REJECTED';
  }
  ```

**2. Domain: Bid Entity с самомодификацией**

**Расположение:** `src/Trade/Domain/Bid/Entity/Bid.php`

- [ ] Создать сущность Bid
  ```php
  #[ORM\Entity(repositoryClass: BidRepository::class)]
  #[ORM\Table(schema: 'trade')]
  class Bid
  {
      #[ORM\Id]
      #[ORM\Column(type: 'uuid')]
      private Id $id;

      #[ORM\ManyToOne(targetEntity: Lot::class)]
      #[ORM\JoinColumn(nullable: false)]
      private Lot $lot;

      #[ORM\ManyToOne(targetEntity: Contractor::class)]
      #[ORM\JoinColumn(nullable: false)]
      private Contractor $contractor;

      #[ORM\Column]
      private int $requestedVolume;

      #[ORM\Column]
      private int $allocatedVolume = 0;

      #[ORM\Column]
      private int $pricePerUnit;

      #[ORM\Column(enumType: BidStatusEnum::class)]
      private BidStatusEnum $status;

      #[ORM\Column(nullable: true)]
      private ?string $rejectionReason = null;

      #[ORM\Column]
      private DateTimeImmutable $createdAt;

      #[ORM\Column(nullable: true)]
      private ?DateTimeImmutable $updatedAt = null;

      /**
       * Фабричный метод: создать ставку в ожидании
       */
      public static function createPending(
          Id $lotId,
          Id $contractorId,
          int $requestedVolume,
          int $pricePerUnit,
      ): self {
          $bid = new self();
          $bid->id = Id::next();
          $bid->lotId = $lotId;
          $bid->contractorId = $contractorId;
          $bid->requestedVolume = $requestedVolume;
          $bid->pricePerUnit = $pricePerUnit;
          $bid->status = BidStatusEnum::PENDING;
          $bid->allocatedVolume = 0;
          $bid->createdAt = new DateTimeImmutable();

          return $bid;
      }

      /**
       * Выделить объём ставке
       */
      public function allocate(int $volume): void
      {
          $this->allocatedVolume = $volume;

          if ($volume === $this->requestedVolume) {
              $this->status = BidStatusEnum::ACTIVE;
          } elseif ($volume > 0) {
              $this->status = BidStatusEnum::PARTIALLY_ACTIVE;
          }

          $this->updatedAt = new DateTimeImmutable();
      }

      /**
       * Вытеснить ставку (полностью или частично)
       * @return int — вытесненный объём
       */
      public function displace(int $volume): int
      {
          if ($volume >= $this->allocatedVolume) {
              // Полное вытеснение
              $displaced = $this->allocatedVolume;
              $this->allocatedVolume = 0;
              $this->status = BidStatusEnum::OUTBID;
              $this->updatedAt = new DateTimeImmutable();
              return $displaced;
          }

          // Частичное вытеснение
          $this->allocatedVolume -= $volume;
          $this->status = BidStatusEnum::PARTIALLY_ACTIVE;
          $this->updatedAt = new DateTimeImmutable();
          return $volume;
      }

      /**
       * Отклонить ставку
       */
      public function reject(string $reason): void
      {
          $this->allocatedVolume = 0;
          $this->status = BidStatusEnum::REJECTED;
          $this->rejectionReason = $reason;
          $this->updatedAt = new DateTimeImmutable();
      }
  }
  ```

**3. Domain: BidCollection (Value Object)**

**Расположение:** `src/Trade/Domain/Bid/Collection/BidCollection.php`

- [ ] Создать BidCollection
  ```php
  final class BidCollection implements \IteratorAggregate, \Countable
  {
      /** @var array<Bid> */
      private array $bids;

      public function __construct(Bid ...$bids)
      {
          $this->bids = $bids;
      }

      /**
       * Получить ставки хуже заданной цены (для вытеснения)
       */
      public function getWorseThan(int $pricePerUnit): self
      {
          $worse = array_filter(
              $this->bids,
              fn(Bid $bid) => $bid->getPricePerUnit() > $pricePerUnit
                  && $bid->hasAllocatedVolume()
          );

          // Сортировка: сначала самые дорогие, при равенстве — новые (LIFO)
          usort($worse, function (Bid $a, Bid $b) {
              if ($a->getPricePerUnit() === $b->getPricePerUnit()) {
                  return $b->getCreatedAt() <=> $a->getCreatedAt();
              }
              return $b->getPricePerUnit() <=> $a->getPricePerUnit();
          });

          return new self(...$worse);
      }

      public function getTotalAllocatedVolume(): int
      {
          return array_sum(
              array_map(fn(Bid $bid) => $bid->getAllocatedVolume(), $this->bids)
          );
      }

      public function add(Bid $bid): void
      {
          $this->bids[] = $bid;
      }

      public function getIterator(): \Traversable
      {
          return new \ArrayIterator($this->bids);
      }

      public function count(): int
      {
          return count($this->bids);
      }
  }
  ```

**4. Domain: Strategy Pattern для размещения ставок**

**Расположение:** `src/Trade/Domain/Auction/Strategy/`

- [ ] **BidAllocationStrategyInterface.php**
  ```php
  interface BidAllocationStrategyInterface
  {
      public function allocate(
          Lot $lot,
          BidCollection $existingBids,
          Bid $newBid
      ): AllocationResult;
  }
  ```

- [ ] **AllocationResult.php** (Result Object)
  ```php
  final readonly class AllocationResult
  {
      public function __construct(
          public BidCollection $modifiedBids,
          public int $newReservedVolume,
      ) {}
  }
  ```

- [ ] **PriceBasedAllocationStrategy.php**
  ```php
  final class PriceBasedAllocationStrategy implements BidAllocationStrategyInterface
  {
      public function allocate(
          Lot $lot,
          BidCollection $existingBids,
          Bid $newBid
      ): AllocationResult {
          // 1. Вычисляем свободный объём
          $remaining = $newBid->getRequestedVolume();
          $freeVolume = $lot->getFreeVolume();

          $allocatedFromFree = min($remaining, $freeVolume);
          $remaining -= $allocatedFromFree;

          // 2. Вытесняем худшие ставки
          $modifiedBids = new BidCollection();

          if ($remaining > 0) {
              $worseBids = $existingBids->getWorseThan($newBid->getPricePerUnit());

              foreach ($worseBids as $bid) {
                  if ($remaining <= 0) break;

                  $displacedVolume = min($bid->getAllocatedVolume(), $remaining);
                  $bid->displace($displacedVolume);
                  $remaining -= $displacedVolume;

                  $modifiedBids->add($bid);
              }
          }

          // 3. Размещаем новую ставку
          $allocatedVolume = $newBid->getRequestedVolume() - $remaining;

          if ($allocatedVolume > 0) {
              $newBid->allocate($allocatedVolume);
          } else {
              $newBid->reject('Недостаточно свободного объёма');
          }

          // 4. Пересчитываем зарезервированный объём
          $totalReserved = $existingBids->getTotalAllocatedVolume();
          $totalReserved -= $modifiedBids->getDisplacedVolume();
          $totalReserved += $newBid->getAllocatedVolume();

          return new AllocationResult(
              modifiedBids: $modifiedBids,
              newReservedVolume: $totalReserved,
          );
      }
  }
  ```

**5. Domain: Бизнес-метод Lot::placeBid()**

**Дополнить сущность Lot:**

- [ ] Добавить метод `placeBid()`
  ```php
  /**
   * Бизнес-операция: разместить ставку в аукционе
   */
  public function placeBid(
      BidCollection $existingBids,
      Bid $newBid,
      BidAllocationStrategyInterface $strategy
  ): BidPlacementResult {
      // 1. Проверка бизнес-правил
      if (!$this->canAcceptBids()) {
          throw new DomainException('Lot is not open for bids');
      }

      // 2. Выполняем стратегию размещения
      $allocationResult = $strategy->allocate($this, $existingBids, $newBid);

      // 3. Обновляем зарезервированный объём
      $this->volume->setReservedVolume($allocationResult->newReservedVolume);

      // 4. Защита инварианта
      if ($this->volume->getReservedVolume() > $this->volume->getTotalVolume()) {
          throw new DomainException('Reserved volume exceeds total volume');
      }

      $this->updatedAt = new DateTimeImmutable();

      return new BidPlacementResult(
          newBid: $newBid,
          modifiedBids: $allocationResult->modifiedBids,
          lotReservedVolume: $this->volume->getReservedVolume(),
      );
  }
  ```

**6. Domain: BidPlacementResult**

**Расположение:** `src/Trade/Domain/Lot/Result/BidPlacementResult.php`

- [ ] Создать Result Object
  ```php
  final readonly class BidPlacementResult
  {
      public function __construct(
          public Bid $newBid,
          public BidCollection $modifiedBids,
          public int $lotReservedVolume,
      ) {}

      public function isSuccess(): bool
      {
          return $this->newBid->isAccepted();
      }

      public function getAllBidsToSave(): array
      {
          return array_merge(
              [$this->newBid],
              iterator_to_array($this->modifiedBids)
          );
      }
  }
  ```

**7. Domain: Repository Interfaces**

- [ ] **BidRepositoryInterface**
  ```php
  interface BidRepositoryInterface
  {
      public function add(Bid $bid): void;
      public function update(Bid $bid): void;
      public function findActiveBidsForUpdate(Id $lotId): BidCollection;
  }
  ```

- [ ] Дополнить **LotRepositoryInterface**
  ```php
  public function lockForUpdate(Id $id): Lot;
  ```

**8. Infra: Repositories**

- [ ] **BidRepository**
  ```php
  public function findActiveBidsForUpdate(Id $lotId): BidCollection
  {
      $qb = $this->em->createQueryBuilder();
      $qb->select('b')
         ->from(Bid::class, 'b')
         ->where('b.lot = :lotId')
         ->andWhere('b.allocatedVolume > 0')
         ->setParameter('lotId', $lotId)
         ->setLockMode(LockMode::PESSIMISTIC_WRITE);

      $bids = $qb->getQuery()->getResult();

      return new BidCollection(...$bids);
  }
  ```

- [ ] Дополнить **LotRepository**
  ```php
  public function lockForUpdate(Id $id): Lot
  {
      $lot = $this->em->find(
          Lot::class,
          $id,
          LockMode::PESSIMISTIC_WRITE
      );

      if ($lot === null) {
          throw new NotFoundException('Lot not found');
      }

      return $lot;
  }
  ```

**9. Application: Command + Handler**

**Расположение:** `src/Trade/Application/Bid/Command/Place/`

- [ ] **Command.php**
  ```php
  final readonly class Command
  {
      public function __construct(
          public string $lotId,
          public string $contractorId,
          public int $requestedVolume,
          public int $pricePerUnit,
      ) {}
  }
  ```

- [ ] **Handler.php** (с использованием Lot::placeBid())
  ```php
  final readonly class Handler implements CommandHandlerInterface
  {
      public function __construct(
          private LotRepositoryInterface $lotRepository,
          private BidRepositoryInterface $bidRepository,
          private ContractorRepositoryInterface $contractorRepository,
          private BidAllocationStrategyInterface $allocationStrategy,
      ) {}

      public function __invoke(Command $command): void
      {
          // 1. Загружаем лот с пессимистической блокировкой
          $lot = $this->lotRepository->lockForUpdate(new Id($command->lotId));

          // 2. Загружаем активные ставки
          $existingBids = $this->bidRepository->findActiveBidsForUpdate($lot->getId());

          // 3. Проверяем подрядчика
          $contractor = $this->contractorRepository->get(new Id($command->contractorId));

          // 4. Создаём новую ставку
          $newBid = Bid::createPending(
              lotId: $lot->getId(),
              contractorId: $contractor->getId(),
              requestedVolume: $command->requestedVolume,
              pricePerUnit: $command->pricePerUnit,
          );

          // 5. БИЗНЕС-ОПЕРАЦИЯ: размещаем ставку через агрегат Lot
          $result = $lot->placeBid(
              existingBids: $existingBids,
              newBid: $newBid,
              strategy: $this->allocationStrategy,
          );

          // 6. Персистим новую ставку
          $this->bidRepository->add($result->newBid);

          // 7. Обновляем вытесненные ставки
          foreach ($result->modifiedBids as $bid) {
              $this->bidRepository->update($bid);
          }

          // 8. Обновляем лот
          $this->lotRepository->update($lot);

          // Транзакция коммитится автоматически через command.bus middleware
      }
  }
  ```

**10. UI: HTTP Action**

**Расположение:** `src/Trade/UI/Http/V1/Bid/Place/`

- [ ] **Request.php**
  ```php
  final readonly class Request
  {
      public function __construct(
          #[Assert\Uuid]
          public string $lot_id,

          #[Assert\Positive]
          public int $requested_volume,

          #[Assert\Positive]
          public int $price_per_unit,
      ) {}
  }
  ```

- [ ] **Response.php**
  ```php
  final readonly class Response
  {
      public function __construct(
          public string $bid_id,
          public int $allocated_volume,
          public int $requested_volume,
          public string $status,
      ) {}
  }
  ```

- [ ] **Action.php** (с x-user-id заголовком)
  ```php
  #[Route('/api/v1/trade/bid', methods: ['POST'])]
  #[OA\Post(summary: 'Place bid', tags: ['Trade - Bids'])]
  final class Action extends AbstractController
  {
      public function __invoke(
          Request $request,
          #[MapRequestPayload] string $xUserId
      ): JsonResponse {
          $this->commandBus->dispatch(
              new Command(
                  lotId: $request->lot_id,
                  contractorId: $xUserId,
                  requestedVolume: $request->requested_volume,
                  pricePerUnit: $request->price_per_unit,
              )
          );

          return new JsonResponse(new ResponseWrapper(/* ... */));
      }
  }
  ```

**11. Migration: Таблица bid**

- [ ] Создать миграцию
  ```sql
  SET search_path TO trade;

  CREATE TABLE bid (
      id UUID PRIMARY KEY,
      lot_id UUID NOT NULL REFERENCES lot(id) ON DELETE CASCADE,
      contractor_id UUID NOT NULL REFERENCES contractor(id),
      requested_volume INT NOT NULL CHECK (requested_volume > 0),
      allocated_volume INT NOT NULL DEFAULT 0 CHECK (allocated_volume >= 0 AND allocated_volume <= requested_volume),
      price_per_unit INT NOT NULL CHECK (price_per_unit > 0),
      status VARCHAR(30) NOT NULL,
      rejection_reason TEXT,
      created_at TIMESTAMP NOT NULL,
      updated_at TIMESTAMP
  );

  CREATE INDEX idx_bid_lot_id ON bid(lot_id);
  CREATE INDEX idx_bid_contractor_id ON bid(contractor_id);
  CREATE INDEX idx_bid_lot_price_allocated ON bid(lot_id, price_per_unit DESC, allocated_volume)
      WHERE allocated_volume > 0;
  ```

**12. Test: Integration Tests**

**Расположение:** `tests/Test/Integration/Trade/Bid/PlaceBidTest.php`

- [ ] Тест успешного размещения (свободный объём)
- [ ] Тест частичного выделения
- [ ] Тест полного вытеснения худших ставок
- [ ] Тест отклонения (нет объёма)
- [ ] Тест конкурентного размещения

**Результат Endpoint #2:**
- ✅ Можно размещать ставки через POST /api/v1/trade/bid
- ✅ Реализован алгоритм вытеснения худших ставок
- ✅ Используется тактический DDD (Lot::placeBid + Strategy Pattern)
- ✅ Конкурентность через пессимистические блокировки

---

### Endpoint #3: GET /api/v1/trade/lot/{id} (Получить лот)

**Цель:** Реализовать чтение информации о конкретном лоте

#### Что реализуем:

**1. Application: Query + Result**

**Расположение:** `src/Trade/Application/Lot/Query/Get/`

- [ ] **Query.php**
  ```php
  final readonly class Query
  {
      public function __construct(public string $lotId) {}
  }
  ```

- [ ] **Result.php**
  ```php
  final readonly class Result
  {
      public function __construct(
          public string $id,
          public string $cargoTypeName,
          public int $totalVolume,
          public int $reservedVolume,
          public int $freeVolume,
          public int $startPrice,
          public int $priceStep,
          public string $status,
          public string $opensAt,
          public string $closesAt,
          public ?string $closeReason,
      ) {}
  }
  ```

- [ ] **Handler.php**
  ```php
  final readonly class Handler implements QueryHandlerInterface
  {
      public function __construct(
          private LotFetcherInterface $fetcher
      ) {}

      public function __invoke(Query $query): Result
      {
          return $this->fetcher->get(new Id($query->lotId));
      }
  }
  ```

**2. Infra: LotFetcher**

**Расположение:** `src/Trade/Infra/Lot/Fetcher/LotFetcher.php`

- [ ] Создать Fetcher с оптимизированными JOIN
  ```php
  final class LotFetcher implements LotFetcherInterface
  {
      public function get(Id $lotId): Result
      {
          $qb = $this->connection->createQueryBuilder();
          $data = $qb
              ->select([
                  'l.id',
                  'ct.name as cargo_type_name',
                  'l.total_volume',
                  'l.reserved_volume',
                  /* ... */
              ])
              ->from('trade.lot', 'l')
              ->innerJoin('l', 'trade.cargo_type', 'ct', 'l.cargo_type_id = ct.id')
              ->where('l.id = :id')
              ->setParameter('id', $lotId->getValue())
              ->fetchAssociative();

          if (!$data) {
              throw new NotFoundException('Lot not found');
          }

          return new Result(/* map from data */);
      }
  }
  ```

**3. UI: HTTP Action**

**Расположение:** `src/Trade/UI/Http/V1/Lot/Get/`

- [ ] **Response.php**
- [ ] **Action.php**

**Результат Endpoint #3:**
- ✅ Можно получить информацию о лоте через GET /api/v1/trade/lot/{id}

---

### Endpoint #4: GET /api/v1/trade/lot (Список лотов)

**Цель:** Получить список лотов с фильтрацией и пагинацией

#### Что реализуем:

**1. Application: Query + Result**

**Расположение:** `src/Trade/Application/Lot/Query/GetList/`

- [ ] **Query.php** (с параметрами фильтрации)
  ```php
  final readonly class Query
  {
      public function __construct(
          public ?string $status = null,
          public int $page = 1,
          public int $limit = 20,
      ) {}
  }
  ```

- [ ] **Result.php** (массив лотов)
- [ ] **Handler.php**

**2. Infra: LotFetcher::getList()**

- [ ] Реализовать метод с пагинацией и фильтрацией

**3. UI: HTTP Action**

**Расположение:** `src/Trade/UI/Http/V1/Lot/GetList/`

- [ ] **Request.php** (query parameters)
- [ ] **Response.php**
- [ ] **Action.php**

**Результат Endpoint #4:**
- ✅ Можно получить список лотов через GET /api/v1/trade/lot?status=OPEN

---

### Endpoint #5: GET /api/v1/trade/lot/{id}/bids (Ставки по лоту)

**Цель:** Получить список ставок для конкретного лота

#### Что реализуем:

**1. Application: Query + Result**

**Расположение:** `src/Trade/Application/Bid/Query/GetByLot/`

- [ ] **Query.php**
- [ ] **Result.php** (массив ставок с информацией о подрядчике)
- [ ] **Handler.php**

**2. Infra: BidFetcher**

**Расположение:** `src/Trade/Infra/Bid/Fetcher/BidFetcher.php`

- [ ] Создать Fetcher с JOIN на contractor

**3. UI: HTTP Action**

**Расположение:** `src/Trade/UI/Http/V1/Bid/GetByLot/`

- [ ] **Response.php**
- [ ] **Action.php**

**Результат Endpoint #5:**
- ✅ Можно просмотреть ставки по лоту через GET /api/v1/trade/lot/{id}/bids

---

### Endpoint #6: GET /api/v1/trade/contractor/bids (Ставки подрядчика)

**Цель:** Получить список ставок подрядчика (с x-user-id)

#### Что реализуем:

**1. Application: Query + Result**

**Расположение:** `src/Trade/Application/Bid/Query/GetByContractor/`

- [ ] **Query.php** (contractorId)
- [ ] **Result.php**
- [ ] **Handler.php**

**2. Infra: BidFetcher::getByContractor()**

- [ ] Реализовать метод

**3. UI: HTTP Action**

**Расположение:** `src/Trade/UI/Http/V1/Bid/GetByContractor/`

- [ ] **Action.php** (читает x-user-id)

**Результат Endpoint #6:**
- ✅ Подрядчик видит свои ставки

---

### Console Command #1: trade:open-lots (Автооткрытие лотов)

**Цель:** Автоматически открывать лоты по времени

#### Что реализуем:

**1. Domain: Метод Lot::open()**

- [ ] Добавить в Lot
  ```php
  public function open(): void
  {
      if ($this->status !== LotStatusEnum::CREATED) {
          throw new DomainException('Lot cannot be opened');
      }

      if (new DateTimeImmutable() < $this->opensAt) {
          throw new DomainException('Lot opens_at time not reached');
      }

      $this->status = LotStatusEnum::OPEN;
      $this->updatedAt = new DateTimeImmutable();
  }
  ```

**2. Domain: Event LotOpenedEvent**

**Расположение:** `src/Trade/Domain/Event/LotOpenedEvent.php`

- [ ] Создать событие

**3. Application: Command OpenLot**

**Расположение:** `src/Trade/Application/Lot/Command/Open/`

- [ ] **Command.php**
- [ ] **Handler.php** (вызывает $lot->open() и публикует событие)

**4. Domain: Repository метод findLotsToOpen()**

- [ ] Добавить в LotRepositoryInterface
  ```php
  public function findLotsToOpen(DateTimeImmutable $now): array;
  ```

**5. UI: Console Command**

**Расположение:** `src/Trade/UI/Console/OpenLotsCommand.php`

- [ ] Создать Symfony Console Command
  ```php
  protected function execute(InputInterface $input, OutputInterface $output): int
  {
      $lots = $this->lotRepository->findLotsToOpen(new DateTimeImmutable());

      foreach ($lots as $lot) {
          $this->commandBus->dispatch(new OpenLotCommand($lot->getId()));
      }

      $output->writeln(sprintf('Opened %d lots', count($lots)));

      return Command::SUCCESS;
  }
  ```

**Результат Console #1:**
- ✅ Лоты автоматически открываются по cron

---

### Console Command #2: trade:calculate-winners (Определение победителей)

**Цель:** Закрывать лоты и определять победителей

#### Что реализуем:

**1. Domain: Метод Lot::close()**

- [ ] Добавить в Lot
  ```php
  public function close(CloseReasonEnum $reason): void
  {
      if ($this->status !== LotStatusEnum::OPEN) {
          throw new DomainException('Lot cannot be closed');
      }

      $this->status = LotStatusEnum::CLOSED;
      $this->termination->setCloseReason($reason);
      $this->updatedAt = new DateTimeImmutable();
  }
  ```

**2. Domain: Events**

**Расположение:** `src/Trade/Domain/Event/`

- [ ] `LotCreatedEvent`
- [ ] `LotOpenedEvent`
- [ ] `LotClosedEvent`
- [ ] `BidPlacedEvent`
- [ ] `BidEvictedEvent`
- [ ] `WinnerDeterminatedEvent`

**3. Application: Event Listeners**

**Расположение:** `src/Trade/Application/Listener/`

- [ ] **EventLoggerListener** (логирует все события в var/log/events.log)
  ```php
  final readonly class EventLoggerListener implements EventListenerInterface
  {
      public function __construct(
          private LoggerInterface $eventLogger,
      ) {}

      public function __invoke(object $event): void
      {
          $this->eventLogger->info('Domain event occurred', [
              'event_type' => $event::class,
              'event_data' => json_encode($event),
              'occurred_at' => (new DateTimeImmutable())->format(DateTimeImmutable::ATOM),
          ]);
      }
  }
  ```

**4. Application: Command CloseLot**

**Расположение:** `src/Trade/Application/Lot/Command/Close/`

- [ ] **Command.php**
- [ ] **Handler.php** (вызывает $lot->close() и публикует события)

**5. UI: Console Command**

**Расположение:** `src/Trade/UI/Console/CalculateWinnersCommand.php`

- [ ] Создать Console Command
  ```php
  protected function execute(InputInterface $input, OutputInterface $output): int
  {
      $lots = $this->lotRepository->findLotsToClose(new DateTimeImmutable());

      foreach ($lots as $lot) {
          $winners = $this->bidRepository->findAllocatedBidsForLotForUpdate($lot->getId());

          $this->commandBus->dispatch(new CloseLotCommand($lot->getId(), CloseReasonEnum::EXPIRED));

          $this->eventBus->publish(new WinnerDeterminatedEvent($lot->getId(), $winners));
      }

      return Command::SUCCESS;
  }
  ```

**6. Config: Monolog для событий**

**Расположение:** `config/packages/monolog.yaml`

- [ ] Настроить handler для event_bus канала
  ```yaml
  when@dev:
      monolog:
          handlers:
              event_log:
                  type: stream
                  path: '%kernel.logs_dir%/events.log'
                  level: info
                  channels: ['event_bus']
                  formatter: monolog.formatter.json
  ```

**Результат Console #2:**
- ✅ Лоты закрываются автоматически
- ✅ Победители определяются
- ✅ События логируются в var/log/events.log

---

## API Documentation

**После реализации всех endpoints:**

- [ ] Добавить OpenAPI аннотации для всех endpoints
- [ ] Настроить теги: `Trade - Lots`, `Trade - Bids`
- [ ] Документировать Request/Response schemas
- [ ] Документировать ошибки (422, 404)

---

## Code Quality

**После завершения разработки:**

- [ ] Запустить ECS: `vendor/bin/ecs check src/Trade`
- [ ] Исправить нарушения: `vendor/bin/ecs check src/Trade --fix`
- [ ] Проверить покрытие тестами: `php bin/phpunit --coverage-html var/coverage`

---

## Критерии завершения

### MVP
- [ ] Можно создать лот через API
- [ ] Можно разместить ставку через API
- [ ] Ставки корректно распределяют объём
- [ ] Худшие ставки вытесняются лучшими
- [ ] Инвариант `reserved_volume <= total_volume` соблюдается
- [ ] Integration tests проходят

### Full Feature
- [ ] Лоты автоматически открываются по времени
- [ ] Лоты автоматически закрываются по времени
- [ ] Победители определяются корректно
- [ ] События публикуются в лог
- [ ] Можно получить список лотов через API
- [ ] Можно получить список ставок через API

### Production Ready
- [ ] Code coverage >= 80%
- [ ] API документация полная
- [ ] ECS проверки проходят

---

## Команды для разработки

### Инициализация
```bash
make b-shell
mkdir -p src/Trade/{UI/Http/V1,UI/Console,Application,Domain,Infra}
composer dump-autoload
php bin/console do:mi:diff
php bin/console do:mi:mi --no-interaction
```

### Тестирование
```bash
php bin/phpunit --filter=Trade
php bin/phpunit tests/Test/Integration/Trade/Bid/PlaceBidTest.php
```

### Консольные команды
```bash
php bin/console trade:open-lots
php bin/console trade:calculate-winners
tail -f var/log/events.log
```

---

## Заключение

Этот план реализован по **endpoint-driven** подходу с использованием **тактического DDD** из Auction-Algorithm-Implementation.md:

- ✅ Разработка вертикальными срезами (endpoint → все слои)
- ✅ Бизнес-операция `Lot::placeBid()` инкапсулирует логику аукциона
- ✅ Strategy Pattern для гибкости алгоритма распределения
- ✅ `BidCollection` и `Bid` самомодификация
- ✅ Seed данных в миграциях вместо Admin endpoints
- ✅ Конкурентность через пессимистические блокировки
- ✅ Event Bus с выводом в лог (Monolog)

План готов к исполнению! 🚀
