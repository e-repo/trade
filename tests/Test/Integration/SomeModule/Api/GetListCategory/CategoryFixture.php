<?php

declare(strict_types=1);

namespace Test\Integration\SomeModule\Api\GetListCategory;

use Test\Integration\Common\Fixture\SomeModule\BaseCategoryFixture;

final class CategoryFixture extends BaseCategoryFixture
{
    public static function allItems(): array
    {
        return [
            [
                'id' => '28912aa1-96ee-4631-8e34-14cd2f019e53',
                'name' => 'Категория 1',
                'description' => 'Категория 1 содержит статьи на тему...',
            ],
            [
                'id' => '06879d79-6261-4139-8617-b7ede36421df',
                'name' => 'Категория 2',
                'description' => 'Категория 2 содержит статьи на тему...',
            ],
            [
                'id' => 'f4aaa88c-dbc8-44e3-925f-eb463e79a0fa',
                'name' => 'Категория 3',
                'description' => 'Категория 3 содержит статьи на тему...',
            ],
            [
                'id' => '1dc2647e-9f15-4e0b-9c1e-5ba00b4755f9',
                'name' => 'Категория 4',
                'description' => 'Категория 4 содержит статьи на тему...',
            ],
            [
                'id' => 'ffa5621f-ac1d-424e-803a-7aaa50077391',
                'name' => 'Категория 5',
                'description' => 'Категория 5 содержит статьи на тему...',
            ],
        ];
    }
}
