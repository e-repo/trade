<?php

declare(strict_types=1);

namespace SomeModule\Domain\Post\Repository;

use SomeModule\Domain\Post\Entity\Category;

interface CategoryRepositoryInterface
{
    public function add(Category $category): void;

    public function findByName(string $name): ?Category;

    public function findById(string $id): ?Category;
}
