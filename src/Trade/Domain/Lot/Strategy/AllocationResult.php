<?php

declare(strict_types=1);

namespace Trade\Domain\Lot\Strategy;

use Trade\Domain\Lot\Collection\BidCollection;

/**
 * Result Object — результат работы стратегии размещения
 */
final readonly class AllocationResult
{
    public function __construct(
        public BidCollection $modifiedBids,
        public int $newReservedVolume,
    ) {}
}
