<?php

declare(strict_types=1);

namespace Trade\Domain\Lot\Result;

use Trade\Domain\Lot\Collection\BidCollection;
use Trade\Domain\Lot\Entity\Bid;

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
            $this->modifiedBids->toArray()
        );
    }
}
