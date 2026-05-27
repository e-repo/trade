<?php

declare(strict_types=1);

namespace Trade\Domain\Lot\Repository;

use CoreKit\Domain\Entity\Id;
use Trade\Domain\Lot\Collection\BidCollection;
use Trade\Domain\Lot\Entity\Bid;

interface BidRepositoryInterface
{
    public function add(Bid $bid): void;

    public function get(Id $id): Bid;

    public function findActiveBidsForLotWithLock(Id $lotId): BidCollection;
}
