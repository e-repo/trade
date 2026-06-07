<?php

declare(strict_types=1);

namespace Test\Integration\Trade\Console\CalculateWinners;

use DateTimeImmutable;
use Test\Integration\Common\Fixture\Trade\BaseBidFixture;
use Trade\Domain\Lot\Enum\BidStatusEnum;

final class BidFixture extends BaseBidFixture
{
    public static function allItems(): array
    {
        return [
            [
                'id' => '550e8400-e29b-41d4-a716-446655440080',
                'lotId' => '550e8400-e29b-41d4-a716-446655440070',
                'contractorId' => '550e8400-e29b-41d4-a716-446655440020',
                'requestedVolume' => 500,
                'allocatedVolume' => 500,
                'pricePerTon' => 48000,
                'status' => BidStatusEnum::ACTIVE->value,
                'createdAt' => new DateTimeImmutable('-1 day'),
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440081',
                'lotId' => '550e8400-e29b-41d4-a716-446655440070',
                'contractorId' => '550e8400-e29b-41d4-a716-446655440021',
                'requestedVolume' => 400,
                'allocatedVolume' => 400,
                'pricePerTon' => 49000,
                'status' => BidStatusEnum::ACTIVE->value,
                'createdAt' => new DateTimeImmutable('-1 day'),
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440082',
                'lotId' => '550e8400-e29b-41d4-a716-446655440070',
                'contractorId' => '550e8400-e29b-41d4-a716-446655440022',
                'requestedVolume' => 100,
                'allocatedVolume' => 100,
                'pricePerTon' => 50000,
                'status' => BidStatusEnum::ACTIVE->value,
                'createdAt' => new DateTimeImmutable('-1 day'),
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440083',
                'lotId' => '550e8400-e29b-41d4-a716-446655440071',
                'contractorId' => '550e8400-e29b-41d4-a716-446655440020',
                'requestedVolume' => 300,
                'allocatedVolume' => 300,
                'pricePerTon' => 44000,
                'status' => BidStatusEnum::ACTIVE->value,
                'createdAt' => new DateTimeImmutable('-2 days'),
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440084',
                'lotId' => '550e8400-e29b-41d4-a716-446655440071',
                'contractorId' => '550e8400-e29b-41d4-a716-446655440021',
                'requestedVolume' => 200,
                'allocatedVolume' => 200,
                'pricePerTon' => 45000,
                'status' => BidStatusEnum::ACTIVE->value,
                'createdAt' => new DateTimeImmutable('-2 days'),
            ],
            // Вытесненные ставки для лота 1 (allocatedVolume = 0)
            [
                'id' => '550e8400-e29b-41d4-a716-446655440085',
                'lotId' => '550e8400-e29b-41d4-a716-446655440070',
                'contractorId' => '550e8400-e29b-41d4-a716-446655440023',
                'requestedVolume' => 300,
                'allocatedVolume' => 0,
                'pricePerTon' => 52000,
                'status' => BidStatusEnum::ACTIVE->value,
                'createdAt' => new DateTimeImmutable('-1 day'),
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440086',
                'lotId' => '550e8400-e29b-41d4-a716-446655440070',
                'contractorId' => '550e8400-e29b-41d4-a716-446655440024',
                'requestedVolume' => 150,
                'allocatedVolume' => 0,
                'pricePerTon' => 55000,
                'status' => BidStatusEnum::ACTIVE->value,
                'createdAt' => new DateTimeImmutable('-1 day'),
            ],
            // Вытесненные ставки для лота 2 (allocatedVolume = 0)
            [
                'id' => '550e8400-e29b-41d4-a716-446655440087',
                'lotId' => '550e8400-e29b-41d4-a716-446655440071',
                'contractorId' => '550e8400-e29b-41d4-a716-446655440025',
                'requestedVolume' => 400,
                'allocatedVolume' => 0,
                'pricePerTon' => 47000,
                'status' => BidStatusEnum::ACTIVE->value,
                'createdAt' => new DateTimeImmutable('-2 days'),
            ],
        ];
    }
}
