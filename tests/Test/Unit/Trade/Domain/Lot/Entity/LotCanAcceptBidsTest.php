<?php

declare(strict_types=1);

namespace Test\Unit\Trade\Domain\Lot\Entity;

use Carbon\Carbon;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Trade\Domain\Dictionary\Entity\CargoType;
use Trade\Domain\Dictionary\Entity\VolumeStep;
use Trade\Domain\Lot\Entity\Lot;
use Trade\Domain\Lot\Enum\CloseReasonEnum;
use Trade\Domain\Lot\Enum\LotStatusEnum;

final class LotCanAcceptBidsTest extends TestCase
{
    #[DataProvider('provideCanAcceptBidsScenarios')]
    public function testCanAcceptBids(
        LotStatusEnum $status,
        string $closesAt,
        bool $expected
    ): void {
        Carbon::setTestNow('2026-06-07 12:00:00');

        $lot = $this->createLot(
            opensAt: new DateTimeImmutable('2026-06-07 11:00:00'),
            closesAt: new DateTimeImmutable($closesAt)
        );

        // Принудительно устанавливаем статус через рефлексию
        $reflection = new \ReflectionClass($lot);
        $statusProperty = $reflection->getProperty('status');
        $statusProperty->setValue($lot, $status);

        $result = $lot->canAcceptBids();

        self::assertSame($expected, $result);

        Carbon::setTestNow();
    }

    public static function provideCanAcceptBidsScenarios(): array
    {
        return [
            'status OPEN and closesAt in future' => [
                LotStatusEnum::OPEN,
                '2026-06-07 13:00:00',
                true,
            ],
            'status CREATED and closesAt in future' => [
                LotStatusEnum::CREATED,
                '2026-06-07 13:00:00',
                false,
            ],
            'status CLOSED and closesAt in future' => [
                LotStatusEnum::CLOSED,
                '2026-06-07 13:00:00',
                false,
            ],
        ];
    }

    private function createLot(
        DateTimeImmutable $opensAt,
        DateTimeImmutable $closesAt
    ): Lot {
        $cargoType = $this->createStub(CargoType::class);
        $volumeStep = $this->createStub(VolumeStep::class);
        $volumeStep->method('getValue')->willReturn(100);

        return new Lot(
            cargoType: $cargoType,
            totalVolume: 1000,
            startPrice: 50000,
            priceStep: 1000,
            volumeStep: $volumeStep,
            opensAt: $opensAt,
            closesAt: $closesAt,
        );
    }
}
