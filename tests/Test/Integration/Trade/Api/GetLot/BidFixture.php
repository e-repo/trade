<?php

declare(strict_types=1);

namespace Test\Integration\Trade\Api\GetLot;

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
                'lotId' => '550e8400-e29b-41d4-a716-446655440071',
                'contractorId' => '550e8400-e29b-41d4-a716-446655440020',
                'requestedVolume' => 300,
                'allocatedVolume' => 300,
                'pricePerTon' => 44000,
                'status' => BidStatusEnum::ACTIVE->value,
                'createdAt' => new DateTimeImmutable('-1 day'),
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440081',
                'lotId' => '550e8400-e29b-41d4-a716-446655440071',
                'contractorId' => '550e8400-e29b-41d4-a716-446655440021',
                'requestedVolume' => 200,
                'allocatedVolume' => 200,
                'pricePerTon' => 45000,
                'status' => BidStatusEnum::ACTIVE->value,
                'createdAt' => new DateTimeImmutable('-1 day'),
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440082',
                'lotId' => '550e8400-e29b-41d4-a716-446655440071',
                'contractorId' => '550e8400-e29b-41d4-a716-446655440020',
                'requestedVolume' => 100,
                'allocatedVolume' => 0,
                'pricePerTon' => 48000,
                'status' => BidStatusEnum::ACTIVE->value,
                'createdAt' => new DateTimeImmutable('-1 day'),
            ],
        ];
    }
}
