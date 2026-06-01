<?php

declare(strict_types=1);

namespace Trade\Application\Lot\Command\Open;

final readonly class Command
{
    public function __construct(
        public string $lotId,
    ) {}
}
