<?php

declare(strict_types=1);

namespace Trade\Domain\Lot\Repository;

use CoreKit\Domain\Entity\Id;
use DateTimeImmutable;

/**
 * DTO для передачи детальной информации о лоте с победителями из репозитория
 */
final readonly class LotDetailsDto
{
    /**
     * @param Id $lotId
     * @param string $status
     * @param int $totalVolume
     * @param int $startPrice
     * @param int $priceStep
     * @param DateTimeImmutable $opensAt
     * @param DateTimeImmutable $closesAt
     * @param string|null $closeReason
     * @param array<Id> $winnerContractorIds
     */
    public function __construct(
        public Id $lotId,
        public string $status,
        public int $totalVolume,
        public int $startPrice,
        public int $priceStep,
        public DateTimeImmutable $opensAt,
        public DateTimeImmutable $closesAt,
        public ?string $closeReason,
        public array $winnerContractorIds,
    ) {}
}
