<?php

declare(strict_types=1);

namespace Test\Integration\Trade\Api\GetLot;

use Carbon\Carbon;
use Test\Integration\Common\Fixture\Trade\BaseLotFixture;
use Trade\Domain\Lot\Enum\LotStatusEnum;

final class LotFixture extends BaseLotFixture
{
    public static function allItems(): array
    {
        return [
            [
                'id' => '550e8400-e29b-41d4-a716-446655440070',
                'cargoTypeId' => '550e8400-e29b-41d4-a716-446655440001',
                'volumeStepId' => '550e8400-e29b-41d4-a716-446655440010',
                'totalVolume' => 1000,
                'startPrice' => 50000,
                'priceStep' => 1000,
                'opensAt' => Carbon::now()->subDays(2)->toDateTimeImmutable(),
                'closesAt' => Carbon::now()->addDays(5)->toDateTimeImmutable(),
                'status' => LotStatusEnum::OPEN->value,
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440071',
                'cargoTypeId' => '550e8400-e29b-41d4-a716-446655440001',
                'volumeStepId' => '550e8400-e29b-41d4-a716-446655440010',
                'totalVolume' => 500,
                'startPrice' => 45000,
                'priceStep' => 500,
                'opensAt' => Carbon::now()->subDays(2)->toDateTimeImmutable(),
                'closesAt' => Carbon::now()->addDay()->toDateTimeImmutable(),
                'status' => LotStatusEnum::OPEN->value,
            ],
        ];
    }
}
