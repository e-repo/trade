<?php

declare(strict_types=1);

namespace Trade\Application\Lot\Command\CloseDueLots;

use DateTimeImmutable;

final readonly class Command
{
    public function __construct(
        public DateTimeImmutable $now,
    ) {}
}
