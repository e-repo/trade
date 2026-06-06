<?php

declare(strict_types=1);

namespace Trade\Domain\Lot\Repository;

use CoreKit\Domain\Entity\Id;

/**
 * DTO для передачи данных лота с его выделенными ставками из репозитория
 */
final readonly class LotWithAllocatedBidsDto
{
    /**
     * @param Id $lotId
     * @param array<AllocatedBidDto> $allocatedBids
     */
    public function __construct(
        public Id $lotId,
        public array $allocatedBids,
    ) {}
}
