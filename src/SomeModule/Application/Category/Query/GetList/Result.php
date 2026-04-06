<?php

declare(strict_types=1);

namespace SomeModule\Application\Category\Query\GetList;

use SomeModule\Domain\Post\Entity\Dto\CategoryDto;

final readonly class Result
{
    /**
     * @param CategoryDto[] $categories
     */
    public function __construct(
        public array $categories,
        public int $totalCount,
    ) {}
}
