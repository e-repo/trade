<?php

declare(strict_types=1);

namespace Test\Integration\Trade\Console\CalculateWinners;

use Test\Integration\Common\Fixture\Trade\BaseVolumeStepFixture;

final class VolumeStepFixture extends BaseVolumeStepFixture
{
    public static function allItems(): array
    {
        return [
            [
                'id' => '550e8400-e29b-41d4-a716-446655440010',
                'name' => '25 тонн',
                'value' => 25,
            ],
        ];
    }
}
