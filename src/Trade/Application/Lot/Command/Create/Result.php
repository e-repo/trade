<?php

declare(strict_types=1);

namespace Trade\Application\Lot\Command\Create;

final readonly class Result
{
    public function __construct(
        public string $lotId,
        public string $status,
    ) {}
}
