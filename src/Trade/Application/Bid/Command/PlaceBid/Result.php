<?php

declare(strict_types=1);

namespace Trade\Application\Bid\Command\PlaceBid;

final readonly class Result
{
    public function __construct(
        public string $bidId,
        public string $status,
        public int $allocatedVolume,
        public int $requestedVolume,
    ) {}
}
