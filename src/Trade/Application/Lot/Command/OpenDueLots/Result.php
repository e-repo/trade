<?php

declare(strict_types=1);

namespace Trade\Application\Lot\Command\OpenDueLots;

final readonly class Result
{
    public function __construct(
        public int $totalProcessed,
        public int $successfullyOpened,
        public int $failed,
    ) {}
}
