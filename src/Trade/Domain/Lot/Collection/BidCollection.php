<?php

declare(strict_types=1);

namespace Trade\Domain\Lot\Collection;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Trade\Domain\Lot\Entity\Bid;
use Traversable;

/**
 * Value Object для работы с коллекцией ставок
 */
final class BidCollection implements IteratorAggregate, Countable
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
    public function getWorseThan(int $pricePerTon): self
    {
        $worse = array_filter(
            $this->bids,
            fn(Bid $bid) => $bid->getPricePerTon() > $pricePerTon
                && $bid->hasAllocatedVolume()
        );

        usort($worse, function (Bid $a, Bid $b) {
            // Сначала вытесняем самые дорогие
            if ($a->getPricePerTon() === $b->getPricePerTon()) {
                // При равной цене - самые новые (LIFO)
                return $b->getCreatedAt() <=> $a->getCreatedAt();
            }
            return $b->getPricePerTon() <=> $a->getPricePerTon();
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
     * Добавить ставку в коллекцию
     */
    public function add(Bid $bid): void
    {
        $this->bids[] = $bid;
    }

    /**
     * Получить все ставки как массив
     *
     * @return array<Bid>
     */
    public function toArray(): array
    {
        return $this->bids;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->bids);
    }

    public function count(): int
    {
        return count($this->bids);
    }
}
