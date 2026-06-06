<?php

declare(strict_types=1);

namespace Trade\Domain\Lot\Repository;

use CoreKit\Domain\Entity\Id;

/**
 * DTO для выделенной ставки (allocated_volume > 0)
 */
final readonly class AllocatedBidDto
{
    public function __construct(
        public Id $bidId,
        public Id $contractorId,
        public int $allocatedVolume,
        public int $pricePerTon,
    ) {}
}
