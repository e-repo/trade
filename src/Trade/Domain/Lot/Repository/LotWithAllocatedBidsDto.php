<?php

declare(strict_types=1);

namespace Trade\Domain\Lot\Repository;

use Trade\Domain\Lot\Entity\Lot;

/**
 * DTO для передачи данных лота с его выделенными ставками из репозитория
 */
final readonly class LotWithAllocatedBidsDto
{
    /**
     * @param Lot $lot Заблокированная entity лота (FOR UPDATE)
     * @param array<AllocatedBidDto> $allocatedBids
     */
    public function __construct(
        public Lot $lot,
        public array $allocatedBids,
    ) {}
}
