<?php

declare(strict_types=1);

namespace SomeModule\Domain\Post\Entity\Specification\Category;

final readonly class SpecificationAggregator
{
    public function __construct(
        public UniqueName $uniqueNameSpecification,
    ) {}
}
