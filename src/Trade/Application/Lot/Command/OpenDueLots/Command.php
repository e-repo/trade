<?php

declare(strict_types=1);

namespace Trade\Application\Lot\Command\OpenDueLots;

use DateTimeImmutable;

final readonly class Command
{
    public function __construct(
        public DateTimeImmutable $now,
    ) {}
}
