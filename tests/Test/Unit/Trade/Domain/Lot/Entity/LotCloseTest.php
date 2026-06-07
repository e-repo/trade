<?php

declare(strict_types=1);

namespace Test\Unit\Trade\Domain\Lot\Entity;

use Carbon\Carbon;
use DateTimeImmutable;
use DomainException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Trade\Domain\Dictionary\Entity\CargoType;
use Trade\Domain\Dictionary\Entity\VolumeStep;
use Trade\Domain\Lot\Entity\Lot;
use Trade\Domain\Lot\Enum\CloseReasonEnum;
use Trade\Domain\Lot\Enum\LotStatusEnum;

final class LotCloseTest extends TestCase
{
    #[DataProvider('provideCloseSuccessScenarios')]
    public function testCloseSuccess(CloseReasonEnum $reason): void
    {
        Carbon::setTestNow('2026-06-07 12:00:00');

        $lot = $this->createLot(
            opensAt: new DateTimeImmutable('2026-06-07 11:00:00'),
            closesAt: new DateTimeImmutable('2026-06-07 13:00:00')
        );

        // Открываем лот
        $lot->open();

        // Закрываем лот
        $lot->close($reason);

        self::assertSame(LotStatusEnum::CLOSED, $lot->getStatus());

        Carbon::setTestNow();
    }

    public static function provideCloseSuccessScenarios(): array
    {
        return [
            'close with EXPIRED reason' => [CloseReasonEnum::EXPIRED],
            'close with MANUAL reason' => [CloseReasonEnum::MANUAL],
        ];
    }

    #[DataProvider('provideInvalidCloseScenarios')]
    public function testCloseThrowsException(
        LotStatusEnum $initialStatus,
        string $expectedMessage
    ): void {
        Carbon::setTestNow('2026-06-07 12:00:00');

        $lot = $this->createLot(
            opensAt: new DateTimeImmutable('2026-06-07 11:00:00'),
            closesAt: new DateTimeImmutable('2026-06-07 13:00:00')
        );

        // Устанавливаем начальный статус через рефлексию
        $reflection = new \ReflectionClass($lot);
        $statusProperty = $reflection->getProperty('status');
        $statusProperty->setValue($lot, $initialStatus);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage($expectedMessage);

        $lot->close(CloseReasonEnum::EXPIRED);

        Carbon::setTestNow();
    }

    public static function provideInvalidCloseScenarios(): array
    {
        return [
            'status is CREATED' => [
                LotStatusEnum::CREATED,
                'Lot cannot be closed',
            ],
            'status is already CLOSED' => [
                LotStatusEnum::CLOSED,
                'Lot cannot be closed',
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
