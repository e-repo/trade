<?php

declare(strict_types=1);

namespace Trade\Application\Lot\Query\Get;

use CoreKit\Domain\Entity\Id;

final readonly class Query
{
    public function __construct(
        public Id $lotId,
    ) {}
}
