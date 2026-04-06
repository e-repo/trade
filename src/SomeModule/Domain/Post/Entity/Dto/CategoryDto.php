<?php

declare(strict_types=1);

namespace SomeModule\Domain\Post\Entity\Dto;

use DateTimeImmutable;

final readonly class CategoryDto
{
    public function __construct(
        public string $name,
        public string $description,
        public ?string $id = null,
        public ?DateTimeImmutable $createdAt = null,
    ) {}
}
