<?php

declare(strict_types=1);

namespace Test\Integration\Trade\Console\CalculateWinners;

use Test\Integration\Common\Fixture\Trade\BaseCargoTypeFixture;

final class CargoTypeFixture extends BaseCargoTypeFixture
{
    public static function allItems(): array
    {
        return [
            [
                'id' => '550e8400-e29b-41d4-a716-446655440001',
                'name' => 'Семена подсолнечника',
            ],
        ];
    }
}
