<?php

declare(strict_types=1);

namespace Trade\Application\Bid\Command\PlaceBid;

use DateTimeImmutable;

final readonly class Command
{
    public function __construct(
        public string $lotId,
        public string $contractorId,
        public int $requestedVolume,
        public int $pricePerTon,
    ) {}
}
