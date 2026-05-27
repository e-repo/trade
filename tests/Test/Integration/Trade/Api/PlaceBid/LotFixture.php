<?php

declare(strict_types=1);

namespace Test\Integration\Trade\Api\PlaceBid;

use DateTimeImmutable;
use Test\Integration\Common\Fixture\Trade\BaseLotFixture;
use Trade\Domain\Lot\Enum\LotStatusEnum;

final class LotFixture extends BaseLotFixture
{
    public static function allItems(): array
    {
        return [
            [
                'id' => '550e8400-e29b-41d4-a716-446655440050',
                'cargoTypeId' => '550e8400-e29b-41d4-a716-446655440001',
                'volumeStepId' => '550e8400-e29b-41d4-a716-446655440010',
                'totalVolume' => 1000,
                'startPrice' => 50000,
                'priceStep' => 1000,
                'opensAt' => new DateTimeImmutable('-1 day'),
                'closesAt' => new DateTimeImmutable('+7 days'),
                'status' => LotStatusEnum::OPEN->value,
            ],
        ];
    }
}
