<?php

declare(strict_types=1);

namespace Test\Integration\Trade\Console\CalculateWinners;

use Carbon\Carbon;
use DateTimeImmutable;
use Test\Integration\Common\Fixture\Trade\BaseLotFixture;
use Trade\Domain\Lot\Enum\LotStatusEnum;

final class LotFixture extends BaseLotFixture
{
    public static function allItems(): array
    {
        return [
            // Лот #1: closesAt через 2 дня (от мокнутого времени = -1 день от реального now = ПРОСРОЧЕН)
            [
                'id' => '550e8400-e29b-41d4-a716-446655440070',
                'cargoTypeId' => '550e8400-e29b-41d4-a716-446655440001',
                'volumeStepId' => '550e8400-e29b-41d4-a716-446655440010',
                'totalVolume' => 1000,
                'startPrice' => 50000,
                'priceStep' => 1000,
                'opensAt' => Carbon::now()->subDays(2)->toDateTimeImmutable(),
                'closesAt' => Carbon::now()->addDays(2)->toDateTimeImmutable(),
                'status' => LotStatusEnum::OPEN->value,
            ],
            // Лот #2: closesAt через 1 день (от мокнутого времени = -2 дня от реального now = ПРОСРОЧЕН)
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
            // Лот #3: closesAt через 8 дней (от мокнутого времени = +5 дней от реального now = НЕ ПРОСРОЧЕН)
            [
                'id' => '550e8400-e29b-41d4-a716-446655440072',
                'cargoTypeId' => '550e8400-e29b-41d4-a716-446655440001',
                'volumeStepId' => '550e8400-e29b-41d4-a716-446655440010',
                'totalVolume' => 750,
                'startPrice' => 48000,
                'priceStep' => 800,
                'opensAt' => Carbon::now()->subDay()->toDateTimeImmutable(),
                'closesAt' => Carbon::now()->addDays(8)->toDateTimeImmutable(),
                'status' => LotStatusEnum::OPEN->value,
            ],
        ];
    }
}
