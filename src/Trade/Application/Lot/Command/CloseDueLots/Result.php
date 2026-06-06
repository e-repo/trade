<?php

declare(strict_types=1);

namespace Trade\Application\Lot\Command\CloseDueLots;

final readonly class Result
{
    public function __construct(
        public int $totalProcessed,
        public int $successfullyClosed,
        public int $failed,
    ) {}
}
