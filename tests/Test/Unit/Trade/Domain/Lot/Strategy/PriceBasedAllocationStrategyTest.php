<?php

declare(strict_types=1);

namespace Test\Unit\Trade\Domain\Lot\Strategy;

use Carbon\Carbon;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Trade\Domain\Dictionary\Entity\Contractor;
use Trade\Domain\Dictionary\Entity\CargoType;
use Trade\Domain\Dictionary\Entity\VolumeStep;
use Trade\Domain\Lot\Collection\BidCollection;
use Trade\Domain\Lot\Entity\Bid;
use Trade\Domain\Lot\Entity\Lot;
use Trade\Domain\Lot\Enum\BidStatusEnum;
use Trade\Domain\Lot\Strategy\PriceBasedAllocationStrategy;

final class PriceBasedAllocationStrategyTest extends TestCase
{
    private PriceBasedAllocationStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new PriceBasedAllocationStrategy();
        Carbon::setTestNow('2026-06-07 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[DataProvider('provideAllocationScenarios')]
    public function testAllocate(
        string $scenario,
        int $lotTotalVolume,
        int $lotReservedVolume,
        array $existingBidsData,
        int $newBidVolume,
        int $newBidPrice,
        int $expectedAllocated,
        BidStatusEnum $expectedStatus,
        int $expectedModifiedCount,
        int $expectedTotalReserved,
        ?string $expectedRejectionReason = null,
        ?array $expectedModifiedBidsState = null
    ): void {
        // Arrange: создаём лот
        $lot = $this->createLot($lotTotalVolume, $lotReservedVolume);

        // Arrange: создаём существующие ставки
        $existingBids = new BidCollection();
        $trackedBids = [];
        foreach ($existingBidsData as $index => $bidData) {
            $bid = $this->createBid($lot, $bidData['volume'], $bidData['price']);
            if (isset($bidData['allocated']) && $bidData['allocated'] > 0) {
                $bid->allocate($bidData['allocated']);
            }
            $existingBids->add($bid);
            $trackedBids[$index] = $bid;
        }

        // Arrange: создаём новую ставку
        $newBid = $this->createBid($lot, $newBidVolume, $newBidPrice);

        // Act: размещаем ставку
        $result = $this->strategy->allocate($lot, $existingBids, $newBid);

        // Assert: проверяем новую ставку
        self::assertSame(
            $expectedAllocated,
            $newBid->getAllocatedVolume(),
            "Scenario '{$scenario}': wrong allocated volume"
        );

        self::assertSame(
            $expectedStatus,
            $newBid->getStatus(),
            "Scenario '{$scenario}': wrong bid status"
        );

        if ($expectedRejectionReason !== null) {
            self::assertSame(
                $expectedRejectionReason,
                $newBid->getRejectionReason(),
                "Scenario '{$scenario}': wrong rejection reason"
            );
        }

        // Assert: проверяем количество изменённых ставок
        self::assertSame(
            $expectedModifiedCount,
            $result->modifiedBids->count(),
            "Scenario '{$scenario}': wrong modified bids count"
        );

        // Assert: проверяем общий зарезервированный объём
        self::assertSame(
            $expectedTotalReserved,
            $result->newReservedVolume,
            "Scenario '{$scenario}': wrong total reserved volume"
        );

        // Assert: проверяем состояние изменённых ставок (если указано)
        if ($expectedModifiedBidsState !== null) {
            foreach ($expectedModifiedBidsState as $index => $expectedState) {
                $bid = $trackedBids[$index];
                self::assertSame(
                    $expectedState['allocated'],
                    $bid->getAllocatedVolume(),
                    "Scenario '{$scenario}': wrong allocated volume for bid #{$index}"
                );
                self::assertSame(
                    $expectedState['status'],
                    $bid->getStatus(),
                    "Scenario '{$scenario}': wrong status for bid #{$index}"
                );
            }
        }
    }

    public static function provideAllocationScenarios(): array
    {
        return [
            'allocate into free volume' => [
                'scenario' => 'Allocate into free volume',
                'lotTotalVolume' => 1000,
                'lotReservedVolume' => 0,
                'existingBidsData' => [],
                'newBidVolume' => 500,
                'newBidPrice' => 45000,
                'expectedAllocated' => 500,
                'expectedStatus' => BidStatusEnum::ACTIVE,
                'expectedModifiedCount' => 0,
                'expectedTotalReserved' => 500,
            ],

            'full displacement of worse bid' => [
                'scenario' => 'Full displacement of worse bid',
                'lotTotalVolume' => 1000,
                'lotReservedVolume' => 1000,
                'existingBidsData' => [
                    ['volume' => 500, 'price' => 48000, 'allocated' => 500],
                ],
                'newBidVolume' => 500,
                'newBidPrice' => 45000,
                'expectedAllocated' => 500,
                'expectedStatus' => BidStatusEnum::ACTIVE,
                'expectedModifiedCount' => 1,
                'expectedTotalReserved' => 500, // 0 (вытесненная) + 500 (новая)
                'expectedRejectionReason' => null,
                'expectedModifiedBidsState' => [
                    0 => ['allocated' => 0, 'status' => BidStatusEnum::OUTBID],
                ],
            ],

            'partial displacement of worse bid' => [
                'scenario' => 'Partial displacement of worse bid',
                'lotTotalVolume' => 1000,
                'lotReservedVolume' => 700,
                'existingBidsData' => [
                    ['volume' => 500, 'price' => 48000, 'allocated' => 500],
                ],
                'newBidVolume' => 500,
                'newBidPrice' => 45000,
                'expectedAllocated' => 500,
                'expectedStatus' => BidStatusEnum::ACTIVE,
                'expectedModifiedCount' => 1,
                'expectedTotalReserved' => 800,
                'expectedRejectionReason' => null,
                'expectedModifiedBidsState' => [
                    0 => ['allocated' => 300, 'status' => BidStatusEnum::PARTIALLY_ACTIVE],
                ],
            ],

            'multiple displacements' => [
                'scenario' => 'Multiple displacements',
                'lotTotalVolume' => 1000,
                'lotReservedVolume' => 1000,
                'existingBidsData' => [
                    ['volume' => 300, 'price' => 50000, 'allocated' => 300],
                    ['volume' => 400, 'price' => 49000, 'allocated' => 400],
                    ['volume' => 300, 'price' => 48000, 'allocated' => 300],
                ],
                'newBidVolume' => 600,
                'newBidPrice' => 48500,
                'expectedAllocated' => 600,
                'expectedStatus' => BidStatusEnum::ACTIVE,
                'expectedModifiedCount' => 2,
                'expectedTotalReserved' => 1000,
                'expectedRejectionReason' => null,
                'expectedModifiedBidsState' => [
                    0 => ['allocated' => 0, 'status' => BidStatusEnum::OUTBID],
                    1 => ['allocated' => 100, 'status' => BidStatusEnum::PARTIALLY_ACTIVE],
                    // bid #2 не изменяется (дешевле новой)
                ],
            ],

            'reject when no free volume and no worse bids' => [
                'scenario' => 'Reject when no free volume and no worse bids',
                'lotTotalVolume' => 1000,
                'lotReservedVolume' => 1000,
                'existingBidsData' => [
                    ['volume' => 1000, 'price' => 45000, 'allocated' => 1000],
                ],
                'newBidVolume' => 500,
                'newBidPrice' => 46000,
                'expectedAllocated' => 0,
                'expectedStatus' => BidStatusEnum::REJECTED,
                'expectedModifiedCount' => 0,
                'expectedTotalReserved' => 1000,
                'expectedRejectionReason' => 'Insufficient free volume and no worse bids to displace',
            ],

            'partial allocation when insufficient volume' => [
                'scenario' => 'Partial allocation when insufficient volume',
                'lotTotalVolume' => 1000,
                'lotReservedVolume' => 800,
                'existingBidsData' => [
                    ['volume' => 500, 'price' => 48000, 'allocated' => 500],
                ],
                'newBidVolume' => 800,
                'newBidPrice' => 45000,
                'expectedAllocated' => 700,
                'expectedStatus' => BidStatusEnum::PARTIALLY_ACTIVE,
                'expectedModifiedCount' => 1,
                'expectedTotalReserved' => 700, // 0 (полностью вытеснена) + 700 (новая частично)
                'expectedRejectionReason' => null,
                'expectedModifiedBidsState' => [
                    0 => ['allocated' => 0, 'status' => BidStatusEnum::OUTBID],
                ],
            ],

            'allocate only free volume when all bids are cheaper' => [
                'scenario' => 'Allocate only free volume when all bids are cheaper',
                'lotTotalVolume' => 1000,
                'lotReservedVolume' => 900,
                'existingBidsData' => [
                    ['volume' => 400, 'price' => 44000, 'allocated' => 400],
                    ['volume' => 500, 'price' => 43000, 'allocated' => 500],
                ],
                'newBidVolume' => 500,
                'newBidPrice' => 46000,
                'expectedAllocated' => 100,
                'expectedStatus' => BidStatusEnum::PARTIALLY_ACTIVE,
                'expectedModifiedCount' => 0,
                'expectedTotalReserved' => 1000,
            ],
        ];
    }

    private function createLot(int $totalVolume, int $reservedVolume): Lot
    {
        $cargoType = $this->createStub(CargoType::class);
        $volumeStep = $this->createStub(VolumeStep::class);
        $volumeStep->method('getValue')->willReturn(100);

        $lot = new Lot(
            cargoType: $cargoType,
            totalVolume: $totalVolume,
            startPrice: 50000,
            priceStep: 1000,
            volumeStep: $volumeStep,
            opensAt: new DateTimeImmutable('2026-06-07 11:00:00'),
            closesAt: new DateTimeImmutable('2026-06-07 13:00:00'),
        );

        // Устанавливаем зарезервированный объём через рефлексию
        if ($reservedVolume > 0) {
            $reflection = new \ReflectionClass($lot);
            $volumeProperty = $reflection->getProperty('volume');
            $volume = $volumeProperty->getValue($lot);

            $volumeReflection = new \ReflectionClass($volume);
            $reservedProperty = $volumeReflection->getProperty('reservedVolume');
            $reservedProperty->setValue($volume, $reservedVolume);
        }

        return $lot;
    }

    private function createBid(Lot $lot, int $requestedVolume, int $pricePerTon): Bid
    {
        $contractor = $this->createStub(Contractor::class);

        return Bid::createPending(
            lot: $lot,
            contractor: $contractor,
            requestedVolume: $requestedVolume,
            pricePerTon: $pricePerTon,
        );
    }
}
