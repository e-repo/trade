<?php

declare(strict_types=1);

namespace Trade\Application\Lot\Query\Get;

use DateTimeImmutable;

final readonly class Result
{
    /**
     * @param string $lotId
     * @param string $status
     * @param int $totalVolume
     * @param int $startPrice
     * @param int $priceStep
     * @param DateTimeImmutable $opensAt
     * @param DateTimeImmutable $closesAt
     * @param string|null $closeReason
     * @param array<string> $winnerContractorIds
     */
    public function __construct(
        public string $lotId,
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
