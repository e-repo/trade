# Реализация алгоритма торгов — Тактическая реализация DDD

## Обзор

Описание тактической реализации алгоритма размещения ставок в обратном аукционе с использованием паттерна **Strategy** и инкапсуляции бизнес-логики в агрегате **Lot**.

---

## Выбранный подход: Lot::placeBid() с Strategy Pattern

### Почему Lot отвечает за размещение ставки?

1. **Lot — это аукцион по своей сути.** Размещение ставки — это операция аукциона, а не операция отдельной ставки.
2. **Инвариант принадлежит Lot** — значит, Lot должен контролировать операции, которые его изменяют.
3. **Ubiquitous language** — "Лот принимает ставку" звучит естественно для бизнеса.
4. **Consistency boundary** — вся операция выполняется в контексте одного агрегата (Lot), хотя и затрагивает связанные сущности (Bid).

---

## Реализация

### 1. Агрегат Lot с бизнес-операцией placeBid()

```php
// Domain/Lot/Entity/Lot.php

class Lot
{
    /**
     * Бизнес-операция: разместить ставку в аукционе
     *
     * @param BidCollection $existingBids — текущие активные ставки
     * @param Bid $newBid — новая ставка (ещё не размещённая)
     * @param BidAllocationStrategyInterface $strategy — стратегия размещения
     * @return BidPlacementResult — результат размещения с изменёнными ставками
     */
    public function placeBid(
        BidCollection $existingBids,
        Bid $newBid,
        BidAllocationStrategyInterface $strategy
    ): BidPlacementResult {
        // 1. Проверка бизнес-правил
        if (!$this->canAcceptBids()) {
            throw new LotNotOpenForBidsException();
        }

        // 2. Выполняем стратегию размещения
        $allocationResult = $strategy->allocate($this, $existingBids, $newBid);

        // 3. Обновляем зарезервированный объём лота
        $this->reservedVolume = $allocationResult->newReservedVolume;

        // 4. Защита инварианта
        if ($this->reservedVolume > $this->totalVolume) {
            throw new InvariantViolationException(
                'Reserved volume exceeds total volume'
            );
        }

        // 5. Генерируем доменное событие
        if ($newBid->isAccepted()) {
            $this->recordEvent(new BidPlaced(
                $this->id,
                $newBid->id,
                $newBid->getAllocatedVolume()
            ));
        }

        return new BidPlacementResult(
            newBid: $newBid,
            modifiedBids: $allocationResult->modifiedBids,
            lotReservedVolume: $this->reservedVolume,
        );
    }

    private function getFreeVolume(): int
    {
        return $this->totalVolume - $this->reservedVolume;
    }
}
```

---

### 2. Сущность Bid с методами самомодификации

```php
// Domain/Bid/Entity/Bid.php

class Bid
{
    /**
     * Фабричный метод: создать ставку в ожидании (перед размещением)
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
        $bid->status = BidStatus::PENDING;
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
            $this->status = BidStatus::ACTIVE;
        } elseif ($volume > 0) {
            $this->status = BidStatus::PARTIALLY_ACTIVE;
        }
    }

    /**
     * Вытеснить ставку (полностью или частично)
     *
     * @return int — объём который был вытеснен
     */
    public function displace(int $volume): int
    {
        if ($volume >= $this->allocatedVolume) {
            // Полное вытеснение
            $displaced = $this->allocatedVolume;
            $this->allocatedVolume = 0;
            $this->status = BidStatus::OUTBID;
            return $displaced;
        }

        // Частичное вытеснение
        $this->allocatedVolume -= $volume;
        $this->status = BidStatus::PARTIALLY_ACTIVE;
        return $volume;
    }

    /**
     * Отклонить ставку
     */
    public function reject(string $reason): void
    {
        $this->allocatedVolume = 0;
        $this->status = BidStatus::REJECTED;
        $this->rejectionReason = $reason;
    }
}
```

**Важно:** Bid сам управляет своим состоянием, но не знает о других Bid.

---

### 3. BidCollection — Value Object для работы с коллекцией ставок

```php
// Domain/Bid/Collection/BidCollection.php

final class BidCollection implements \IteratorAggregate, \Countable
{
    /** @var array<Bid> */
    private array $bids;

    public function __construct(Bid ...$bids)
    {
        $this->bids = $bids;
    }

    /**
     * Получить ставки хуже заданной цены, отсортированные для вытеснения
     *
     * Приоритет вытеснения:
     * 1. Сначала вытесняем самые дорогие (худшие предложения)
     * 2. При равенстве цен — самые новые (LIFO)
     */
    public function getWorseThan(int $pricePerUnit): self
    {
        $worse = array_filter(
            $this->bids,
            fn(Bid $bid) => $bid->getPricePerUnit() > $pricePerUnit
                && $bid->hasAllocatedVolume()
        );

        usort($worse, function (Bid $a, Bid $b) {
            // Сначала вытесняем самые дорогие
            if ($a->getPricePerUnit() === $b->getPricePerUnit()) {
                return $b->getCreatedAt() <=> $a->getCreatedAt();
            }
            return $b->getPricePerUnit() <=> $a->getPricePerUnit();
        });

        return new self(...$worse);
    }

    /**
     * Получить суммарный выделенный объём по всем ставкам
     */
    public function getTotalAllocatedVolume(): int
    {
        return array_sum(
            array_map(fn(Bid $bid) => $bid->getAllocatedVolume(), $this->bids)
        );
    }

    /**
     * Получить суммарный вытесненный объём из модифицированных ставок
     */
    public function getDisplacedVolume(): int
    {
        return array_reduce(
            $this->bids,
            function (int $carry, Bid $bid) {
                return $carry + ($bid->getAllocatedVolume() === 0
                    ? $bid->getRequestedVolume()
                    : 0);
            },
            0
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

**Преимущества:**
- Логика сортировки и фильтрации инкапсулирована
- `Lot::placeBid()` становится проще и читабельнее
- Можно переиспользовать в других местах

---

### 4. Strategy Pattern — Стратегия размещения ставок

#### Интерфейс стратегии

```php
// Domain/Auction/Strategy/BidAllocationStrategyInterface.php

interface BidAllocationStrategyInterface
{
    /**
     * Распределить объём для новой ставки, потенциально вытесняя худшие ставки
     */
    public function allocate(
        Lot $lot,
        BidCollection $existingBids,
        Bid $newBid
    ): AllocationResult;
}
```

#### Реализация стратегии на основе цены

```php
// Domain/Auction/Strategy/PriceBasedAllocationStrategy.php

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
                if ($remaining <= 0) {
                    break;
                }

                $displacedVolume = min($bid->getAllocatedVolume(), $remaining);
                $bid->displace($displacedVolume); // Bid умеет себя вытеснять
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

        // 4. Пересчитываем зарезервированный объём лота
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

---

### 5. Result Objects

#### AllocationResult — результат работы стратегии

```php
// Domain/Auction/Result/AllocationResult.php

final readonly class AllocationResult
{
    public function __construct(
        public BidCollection $modifiedBids,
        public int $newReservedVolume,
    ) {}
}
```

#### BidPlacementResult — результат размещения ставки

```php
// Domain/Lot/Result/BidPlacementResult.php

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

    /**
     * Получить все ставки для сохранения (новая + изменённые)
     */
    public function getAllBidsToSave(): array
    {
        return array_merge(
            [$this->newBid],
            iterator_to_array($this->modifiedBids)
        );
    }
}
```

---

### 6. Использование в Application Handler

```php
// Application/Bid/Command/Create/Handler.php

class Handler implements CommandHandlerInterface
{
    public function __construct(
        private readonly LotRepositoryInterface $lotRepository,
        private readonly BidRepositoryInterface $bidRepository,
        private readonly BidAllocationStrategyInterface $allocationStrategy,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(Command $command): void
    {
        // 1. Загружаем лот с пессимистической блокировкой
        $lot = $this->lotRepository->lockForUpdate($command->lotId);

        // 2. Загружаем активные ставки
        $existingBids = $this->bidRepository->findActiveBidsForUpdate($lot->id);

        // 3. Создаём новую ставку (пока не размещённую)
        $newBid = Bid::createPending(
            lotId: $lot->id,
            contractorId: $command->contractorId,
            requestedVolume: $command->requestedVolume,
            pricePerUnit: $command->pricePerUnit,
        );

        // 4. Размещаем ставку через бизнес-метод лота
        $result = $lot->placeBid(
            existingBids: $existingBids,
            newBid: $newBid,
            strategy: $this->allocationStrategy,
        );

        // 5. Персистим всё одной транзакцией
        $this->bidRepository->add($result->newBid);

        foreach ($result->modifiedBids as $bid) {
            $this->bidRepository->update($bid);
        }

        $this->lotRepository->update($lot);

        // 6. Публикуем события
        foreach ($lot->releaseEvents() as $event) {
            $this->eventBus->publish($event);
        }
    }
}
```

---

## Преимущества подхода

### ✅ Инкапсуляция бизнес-логики
Вся логика аукциона внутри `Lot`, алгоритм размещения ставки описан в одном месте.

### ✅ Защита инвариантов
`Lot` контролирует `reserved_volume`, невозможно нарушить инвариант извне.

### ✅ Тестируемость
Можно unit-тестировать `placeBid()` без БД, передавая моки коллекций и ставок.

### ✅ Явное моделирование
`placeBid()` — это ubiquitous language, понятный бизнесу.

### ✅ Гибкость через Strategy
Легко заменить алгоритм размещения (например, для A/B тестирования разных стратегий).

---

## Недостатки подхода

### ❌ Lot работает с коллекцией Bid
Потенциально много объектов в памяти (но для аукциона это норма, обычно десятки-сотни ставок).

---

## Альтернативные стратегии (расширения)

### 1. Временная приоритизация

```php
// Domain/Auction/Strategy/TimeBasedAllocationStrategy.php

class TimeBasedAllocationStrategy implements BidAllocationStrategyInterface
{
    public function allocate(
        Lot $lot,
        BidCollection $existingBids,
        Bid $newBid
    ): AllocationResult {
        // При равной цене — приоритет более ранним ставкам
        // ...
    }
}
```

### 2. Учёт рейтинга подрядчика

```php
// Domain/Auction/Strategy/RatingAwareAllocationStrategy.php

class RatingAwareAllocationStrategy implements BidAllocationStrategyInterface
{
    public function allocate(
        Lot $lot,
        BidCollection $existingBids,
        Bid $newBid
    ): AllocationResult {
        // Небольшое преимущество подрядчикам с высоким рейтингом
        // ...
    }
}
```

### 3. Комбинированная стратегия

```php
// Domain/Auction/Strategy/CompositeAllocationStrategy.php

class CompositeAllocationStrategy implements BidAllocationStrategyInterface
{
    public function __construct(
        private array $strategies,
        private array $weights,
    ) {}

    public function allocate(
        Lot $lot,
        BidCollection $existingBids,
        Bid $newBid
    ): AllocationResult {
        // Комбинирует несколько стратегий с весами
        // ...
    }
}
```

---

## Заключение

Данный подход обеспечивает:

1. **Чистую доменную модель**, отражающую бизнес-реальность
2. **Сильную консистентность** через паттерн агрегата и защиту инвариантов
3. **Гибкость** через паттерн Strategy
4. **Безопасность** через пессимистические блокировки и транзакции
5. **Поддерживаемость** через чёткое разделение ответственности

Подход хорошо масштабируется для типичных сценариев аукционов и может быть расширен дополнительными стратегиями или оптимизациями по мере необходимости.
