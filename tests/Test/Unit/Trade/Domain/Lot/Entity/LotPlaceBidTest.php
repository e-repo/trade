<?php

declare(strict_types=1);

namespace Test\Unit\Trade\Domain\Lot\Entity;

use Carbon\Carbon;
use DateTimeImmutable;
use DomainException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Trade\Domain\Dictionary\Entity\CargoType;
use Trade\Domain\Dictionary\Entity\Contractor;
use Trade\Domain\Dictionary\Entity\VolumeStep;
use Trade\Domain\Lot\Collection\BidCollection;
use Trade\Domain\Lot\Entity\Bid;
use Trade\Domain\Lot\Entity\Lot;
use Trade\Domain\Lot\Enum\LotStatusEnum;
use Trade\Domain\Lot\Result\BidPlacementResult;
use Trade\Domain\Lot\Strategy\AllocationResult;
use Trade\Domain\Lot\Strategy\BidAllocationStrategyInterface;

final class LotPlaceBidTest extends TestCase
{
    public function testPlaceBidSuccess(): void
    {
        Carbon::setTestNow('2026-06-07 12:00:00');

        $lot = $this->createLot(
            totalVolume: 1000,
            opensAt: new DateTimeImmutable('2026-06-07 11:00:00'),
            closesAt: new DateTimeImmutable('2026-06-07 13:00:00')
        );

        // Открываем лот
        $lot->open();

        $existingBids = new BidCollection();
        $newBid = $this->createBid($lot, requestedVolume: 500, allocatedVolume: 500);

        $strategy = $this->createMock(BidAllocationStrategyInterface::class);
        $strategy
            ->expects($this->once())
            ->method('allocate')
            ->with($lot, $existingBids, $newBid)
            ->willReturn(new AllocationResult(
                modifiedBids: new BidCollection(),
                newReservedVolume: 500,
            ));

        $result = $lot->placeBid($existingBids, $newBid, $strategy);

        self::assertInstanceOf(BidPlacementResult::class, $result);
        self::assertSame($newBid, $result->newBid);
        self::assertSame(500, $result->lotReservedVolume);

        Carbon::setTestNow();
    }

    #[DataProvider('provideInvalidPlaceBidScenarios')]
    public function testPlaceBidThrowsExceptionWhenLotNotOpen(
        LotStatusEnum $status,
        string $expectedMessage
    ): void {
        Carbon::setTestNow('2026-06-07 12:00:00');

        $lot = $this->createLot(
            totalVolume: 1000,
            opensAt: new DateTimeImmutable('2026-06-07 11:00:00'),
            closesAt: new DateTimeImmutable('2026-06-07 13:00:00')
        );

        // Устанавливаем статус через рефлексию, TODO - необходимо подумать как
        // убрать в будущем.
        $reflection = new \ReflectionClass($lot);
        $statusProperty = $reflection->getProperty('status');
        $statusProperty->setValue($lot, $status);

        $existingBids = new BidCollection();
        $newBid = $this->createBid($lot, requestedVolume: 500, allocatedVolume: 500);
        $strategy = $this->createStub(BidAllocationStrategyInterface::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage($expectedMessage);

        $lot->placeBid($existingBids, $newBid, $strategy);

        Carbon::setTestNow();
    }

    public static function provideInvalidPlaceBidScenarios(): array
    {
        return [
            'status is CREATED' => [
                LotStatusEnum::CREATED,
                'Lot is not open for bids',
            ],
            'status is CLOSED' => [
                LotStatusEnum::CLOSED,
                'Lot is not open for bids',
            ],
        ];
    }

    public function testPlaceBidThrowsExceptionWhenClosesAtHasPassed(): void
    {
        // Создаем лот в момент когда closesAt еще в будущем
        Carbon::setTestNow('2026-06-07 12:00:00');

        $lot = $this->createLot(
            totalVolume: 1000,
            opensAt: new DateTimeImmutable('2026-06-07 11:00:00'),
            closesAt: new DateTimeImmutable('2026-06-07 13:00:00')
        );

        // Открываем лот
        $lot->open();

        // Переводим время вперед после closesAt
        Carbon::setTestNow('2026-06-07 13:00:01');

        $existingBids = new BidCollection();
        $newBid = $this->createBid($lot, requestedVolume: 500, allocatedVolume: 500);
        $strategy = $this->createStub(BidAllocationStrategyInterface::class);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Lot is not open for bids');

        $lot->placeBid($existingBids, $newBid, $strategy);

        Carbon::setTestNow();
    }

    public function testPlaceBidThrowsExceptionWhenReservedVolumeExceedsTotal(): void
    {
        Carbon::setTestNow('2026-06-07 12:00:00');

        $lot = $this->createLot(
            totalVolume: 1000,
            opensAt: new DateTimeImmutable('2026-06-07 11:00:00'),
            closesAt: new DateTimeImmutable('2026-06-07 13:00:00')
        );

        // Открываем лот
        $lot->open();

        $existingBids = new BidCollection();
        $newBid = $this->createBid($lot, requestedVolume: 1500, allocatedVolume: 1500);

        $strategy = $this->createStub(BidAllocationStrategyInterface::class);
        $strategy
            ->method('allocate')
            ->willReturn(new AllocationResult(
                modifiedBids: new BidCollection(),
                newReservedVolume: 1500, // Превышает totalVolume!
            ));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Reserved volume cannot exceed total volume');

        $lot->placeBid($existingBids, $newBid, $strategy);

        Carbon::setTestNow();
    }

    private function createLot(
        int $totalVolume,
        DateTimeImmutable $opensAt,
        DateTimeImmutable $closesAt
    ): Lot {
        $cargoType = $this->createStub(CargoType::class);
        $volumeStep = $this->createStub(VolumeStep::class);
        $volumeStep->method('getValue')->willReturn(100);

        return new Lot(
            cargoType: $cargoType,
            totalVolume: $totalVolume,
            startPrice: 50000,
            priceStep: 1000,
            volumeStep: $volumeStep,
            opensAt: $opensAt,
            closesAt: $closesAt,
        );
    }

    private function createBid(
        Lot $lot,
        int $requestedVolume,
        int $allocatedVolume
    ): Bid {
        $contractor = $this->createStub(Contractor::class);

        $bid = Bid::createPending(
            lot: $lot,
            contractor: $contractor,
            requestedVolume: $requestedVolume,
            pricePerTon: 45000,
        );

        // Выделяем объём
        if ($allocatedVolume > 0) {
            $bid->allocate($allocatedVolume);
        }

        return $bid;
    }
}
