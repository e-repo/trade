<?php

declare(strict_types=1);

namespace Trade\Application\Lot\Command\Create;

use DateTimeImmutable;

final readonly class Command
{
    public function __construct(
        public string $cargoTypeId,
        public int $totalVolume,
        public int $startPrice,
        public int $priceStep,
        public string $volumeStepId,
        public DateTimeImmutable $opensAt,
        public DateTimeImmutable $closesAt,
    ) {}
}
