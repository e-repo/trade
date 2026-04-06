<?php

declare(strict_types=1);

namespace Test\Integration\SomeModule\Api\CreateCategory;

use Test\Integration\Common\Fixture\SomeModule\BaseCategoryFixture;

final class CategoryFixture extends BaseCategoryFixture
{
    public static function allItems(): array
    {
        return [
            [
                'id' => '28912aa1-96ee-4631-8e34-14cd2f019e53',
                'name' => 'Регуляторы роста',
                'description' => 'Категория регуляторы роста содержит статьи на тему...',
            ],
        ];
    }
}
