<?php

declare(strict_types=1);

namespace Test\Integration\Trade\Console\CalculateWinners;

use Carbon\Carbon;
use CoreKit\Domain\Entity\Id;
use DateTimeImmutable;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Component\Console\Tester\CommandTester;
use Test\Common\FunctionalTestCase;
use Trade\Domain\Event\LotClosedEvent;
use Trade\Domain\Event\WinnerDeterminatedEvent;
use Trade\Domain\Lot\Entity\Lot;
use Trade\Domain\Lot\Enum\CloseReasonEnum;
use Trade\Domain\Lot\Enum\LotStatusEnum;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

final class CalculateWinnersCommandTest extends FunctionalTestCase
{
    use InteractsWithMessenger;

    private const LOT_ID_EXPIRED_1 = '550e8400-e29b-41d4-a716-446655440070';
    private const LOT_ID_EXPIRED_2 = '550e8400-e29b-41d4-a716-446655440071';
    private const LOT_ID_NOT_EXPIRED = '550e8400-e29b-41d4-a716-446655440072';

    public function setUp(): void
    {
        parent::setUp();

        // Мокаем время на 3 дня назад, чтобы обойти валидацию "closesAt должно быть в будущем"
        // Фикстуры создадут лоты с closesAt = Carbon::now() + 1-2 дня (относительно мокнутого времени)
        Carbon::setTestNow(Carbon::now()->subDays(3));

        $this->databaseTool
            ->withPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE)
            ->loadFixtures([
                CargoTypeFixture::class,
                VolumeStepFixture::class,
                ContractorFixture::class,
                LotFixture::class,
                BidFixture::class,
            ]);

        // Возвращаем реальное время - теперь лоты с closesAt в прошлом (просрочены)
        Carbon::setTestNow();
    }

    public function testSuccessCloseMultipleLotsWithWinners(): void
    {
        $command = $this->application->find('trade:calculate-winners');
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([]);

        self::assertEquals(0, $exitCode);
        self::assertStringContainsString('Successfully closed 2 lot(s)', $commandTester->getDisplay());

        $lot1 = $this->entityManager->find(Lot::class, new Id(self::LOT_ID_EXPIRED_1));
        $lot2 = $this->entityManager->find(Lot::class, new Id(self::LOT_ID_EXPIRED_2));
        $lot3 = $this->entityManager->find(Lot::class, new Id(self::LOT_ID_NOT_EXPIRED));

        self::assertEquals(LotStatusEnum::CLOSED, $lot1->getStatus());
        self::assertEquals(LotStatusEnum::CLOSED, $lot2->getStatus());
        self::assertEquals(LotStatusEnum::OPEN, $lot3->getStatus());

        $this->transport('event.bus')->queue()->assertCount(4);
        $this->transport('event.bus')->queue()->assertContains(LotClosedEvent::class, 2);
        $this->transport('event.bus')->queue()->assertContains(WinnerDeterminatedEvent::class, 2);
    }

    public function testNoLotsToClose(): void
    {
        $command = $this->application->find('trade:calculate-winners');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->entityManager->clear();

        $exitCode = $commandTester->execute([]);

        self::assertEquals(0, $exitCode);
        self::assertStringContainsString('No lots to close', $commandTester->getDisplay());
    }

    public function testOnlyExpiredLotsAreClosed(): void
    {
        $command = $this->application->find('trade:calculate-winners');
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([]);

        self::assertEquals(0, $exitCode);

        $query = $this->entityManager->createQuery(
            'SELECT COUNT(l.id) FROM Trade\Domain\Lot\Entity\Lot l WHERE l.status = :status'
        );
        $query->setParameter('status', LotStatusEnum::CLOSED);
        $closedCount = $query->getSingleScalarResult();

        self::assertEquals(2, $closedCount);

        $lotNotExpired = $this->entityManager->find(Lot::class, new Id(self::LOT_ID_NOT_EXPIRED));
        self::assertEquals(LotStatusEnum::OPEN, $lotNotExpired->getStatus());
    }

    public function testAlreadyClosedLotsAreSkipped(): void
    {
        $lot1 = $this->entityManager->find(Lot::class, new Id(self::LOT_ID_EXPIRED_1));
        $lot1->close(CloseReasonEnum::EXPIRED);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $command = $this->application->find('trade:calculate-winners');
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([]);

        self::assertEquals(0, $exitCode);
        self::assertStringContainsString('Successfully closed 1 lot(s)', $commandTester->getDisplay());

        $this->entityManager->clear();
        $lot1 = $this->entityManager->find(Lot::class, new Id(self::LOT_ID_EXPIRED_1));
        self::assertEquals(LotStatusEnum::CLOSED, $lot1->getStatus());

        $lot2 = $this->entityManager->find(Lot::class, new Id(self::LOT_ID_EXPIRED_2));
        self::assertEquals(LotStatusEnum::CLOSED, $lot2->getStatus());
    }

    public function testEventsPublished(): void
    {
        $command = $this->application->find('trade:calculate-winners');
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $lotClosedEnvelopes = $this->transport('event.bus')->queue()->messages(LotClosedEvent::class);
        self::assertCount(2, $lotClosedEnvelopes);

        $winnerEnvelopes = $this->transport('event.bus')->queue()->messages(WinnerDeterminatedEvent::class);
        self::assertCount(2, $winnerEnvelopes);

        $lotClosedEvent = $lotClosedEnvelopes[0];
        self::assertInstanceOf(LotClosedEvent::class, $lotClosedEvent);
        self::assertInstanceOf(Id::class, $lotClosedEvent->lotId);
        self::assertEquals(CloseReasonEnum::EXPIRED, $lotClosedEvent->closeReason);
        self::assertInstanceOf(\DateTimeImmutable::class, $lotClosedEvent->closedAt);
    }

    public function testWinnerDataCorrectness(): void
    {
        $command = $this->application->find('trade:calculate-winners');
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $winnerEnvelopes = $this->transport('event.bus')->queue()->messages(WinnerDeterminatedEvent::class);
        self::assertCount(2, $winnerEnvelopes);

        $event1 = $winnerEnvelopes[0];
        self::assertInstanceOf(WinnerDeterminatedEvent::class, $event1);
        self::assertIsArray($event1->winners);
        self::assertCount(3, $event1->winners);

        $winner1 = $event1->winners[0];
        self::assertArrayHasKey('bidId', $winner1);
        self::assertArrayHasKey('contractorId', $winner1);
        self::assertArrayHasKey('allocatedVolume', $winner1);
        self::assertArrayHasKey('pricePerTon', $winner1);

        self::assertEquals('550e8400-e29b-41d4-a716-446655440080', $winner1['bidId']);
        self::assertEquals('550e8400-e29b-41d4-a716-446655440020', $winner1['contractorId']);
        self::assertEquals(500, $winner1['allocatedVolume']);
        self::assertEquals(48000, $winner1['pricePerTon']);

        $winner2 = $event1->winners[1];
        self::assertEquals('550e8400-e29b-41d4-a716-446655440081', $winner2['bidId']);
        self::assertEquals('550e8400-e29b-41d4-a716-446655440021', $winner2['contractorId']);
        self::assertEquals(400, $winner2['allocatedVolume']);
        self::assertEquals(49000, $winner2['pricePerTon']);

        $winner3 = $event1->winners[2];
        self::assertEquals('550e8400-e29b-41d4-a716-446655440082', $winner3['bidId']);
        self::assertEquals('550e8400-e29b-41d4-a716-446655440022', $winner3['contractorId']);
        self::assertEquals(100, $winner3['allocatedVolume']);
        self::assertEquals(50000, $winner3['pricePerTon']);

        $event2 = $winnerEnvelopes[1];
        self::assertInstanceOf(WinnerDeterminatedEvent::class, $event2);
        self::assertCount(2, $event2->winners);

        $winner4 = $event2->winners[0];
        self::assertEquals('550e8400-e29b-41d4-a716-446655440083', $winner4['bidId']);
        self::assertEquals(300, $winner4['allocatedVolume']);
        self::assertEquals(44000, $winner4['pricePerTon']);

        $winner5 = $event2->winners[1];
        self::assertEquals('550e8400-e29b-41d4-a716-446655440084', $winner5['bidId']);
        self::assertEquals(200, $winner5['allocatedVolume']);
        self::assertEquals(45000, $winner5['pricePerTon']);
    }
}
