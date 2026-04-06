<?php

declare(strict_types=1);

namespace SomeModule\Application\Category\Query\GetList;

final readonly class Query
{
    public function __construct(
        public ?string $name,
        public int $offset,
        public int $limit,
    ) {}
}
