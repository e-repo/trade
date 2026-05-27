<?php

declare(strict_types=1);

namespace Trade\Domain\Lot\Strategy;

use Trade\Domain\Lot\Collection\BidCollection;
use Trade\Domain\Lot\Entity\Bid;
use Trade\Domain\Lot\Entity\Lot;

interface BidAllocationStrategyInterface
{
    public function allocate(
        Lot $lot,
        BidCollection $existingBids,
        Bid $newBid
    ): AllocationResult;
}
