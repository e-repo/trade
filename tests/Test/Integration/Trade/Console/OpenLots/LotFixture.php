<?php

declare(strict_types=1);

namespace Test\Integration\Trade\Console\OpenLots;

use DateTimeImmutable;
use Test\Integration\Common\Fixture\Trade\BaseLotFixture;

final class LotFixture extends BaseLotFixture
{
    public static function allItems(): array
    {
        return [
            // Lot ready to open (opensAt in the past, status CREATED)
            [
                'id' => '550e8400-e29b-41d4-a716-446655440060',
                'cargoTypeId' => '550e8400-e29b-41d4-a716-446655440001',
                'volumeStepId' => '550e8400-e29b-41d4-a716-446655440010',
                'totalVolume' => 1000,
                'startPrice' => 50000,
                'priceStep' => 1000,
                'opensAt' => new DateTimeImmutable('-1 hour'),
                'closesAt' => new DateTimeImmutable('+7 days'),
            ],
            // Lot ready to open (opensAt in the past, status CREATED)
            [
                'id' => '550e8400-e29b-41d4-a716-446655440061',
                'cargoTypeId' => '550e8400-e29b-41d4-a716-446655440001',
                'volumeStepId' => '550e8400-e29b-41d4-a716-446655440010',
                'totalVolume' => 500,
                'startPrice' => 45000,
                'priceStep' => 500,
                'opensAt' => new DateTimeImmutable('-2 hours'),
                'closesAt' => new DateTimeImmutable('+5 days'),
            ],
            // Lot NOT ready to open (opensAt in the future)
            [
                'id' => '550e8400-e29b-41d4-a716-446655440062',
                'cargoTypeId' => '550e8400-e29b-41d4-a716-446655440001',
                'volumeStepId' => '550e8400-e29b-41d4-a716-446655440010',
                'totalVolume' => 750,
                'startPrice' => 48000,
                'priceStep' => 800,
                'opensAt' => new DateTimeImmutable('+1 hour'),
                'closesAt' => new DateTimeImmutable('+10 days'),
            ],
        ];
    }
}
