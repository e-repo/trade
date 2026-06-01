<?php

declare(strict_types=1);

namespace Test\Integration\Trade\Console\OpenLots;

use CoreKit\Domain\Entity\Id;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Component\Console\Tester\CommandTester;
use Test\Common\FunctionalTestCase;
use Trade\Domain\Event\LotOpenedEvent;
use Trade\Domain\Lot\Entity\Lot;
use Trade\Domain\Lot\Enum\LotStatusEnum;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

final class OpenLotsCommandTest extends FunctionalTestCase
{
    use InteractsWithMessenger;

    private const LOT_ID_READY_1 = '550e8400-e29b-41d4-a716-446655440060';
    private const LOT_ID_READY_2 = '550e8400-e29b-41d4-a716-446655440061';
    private const LOT_ID_FUTURE = '550e8400-e29b-41d4-a716-446655440062';

    public function setUp(): void
    {
        parent::setUp();

        $this->databaseTool
            ->withPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE)
            ->loadFixtures([
                CargoTypeFixture::class,
                VolumeStepFixture::class,
                LotFixture::class,
            ]);
    }

    public function testSuccessOpenMultipleLots(): void
    {
        // arrange
        $command = $this->application->find('trade:open-lots');
        $commandTester = new CommandTester($command);

        // act
        $exitCode = $commandTester->execute([]);

        // assert
        self::assertEquals(0, $exitCode);
        self::assertStringContainsString('Successfully opened 2 lot(s)', $commandTester->getDisplay());

        // verify lots are opened in database
        $lot1 = $this->entityManager->find(Lot::class, new Id(self::LOT_ID_READY_1));
        $lot2 = $this->entityManager->find(Lot::class, new Id(self::LOT_ID_READY_2));
        $lot3 = $this->entityManager->find(Lot::class, new Id(self::LOT_ID_FUTURE));

        self::assertEquals(LotStatusEnum::OPEN, $lot1->getStatus());
        self::assertEquals(LotStatusEnum::OPEN, $lot2->getStatus());
        self::assertEquals(LotStatusEnum::CREATED, $lot3->getStatus());

        // verify events were published
        $this->transport('event.bus')->queue()->assertCount(2);
        $this->transport('event.bus')->queue()->assertContains(LotOpenedEvent::class, 2);
    }

    public function testNoLotsToOpen(): void
    {
        // arrange - first open all lots
        $command = $this->application->find('trade:open-lots');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->entityManager->clear();

        // act - run command again
        $exitCode = $commandTester->execute([]);

        // assert
        self::assertEquals(0, $exitCode);
        self::assertStringContainsString('No lots to open', $commandTester->getDisplay());
    }

    public function testOnlyLotsWithPassedOpensAtAreOpened(): void
    {
        // arrange
        $command = $this->application->find('trade:open-lots');
        $commandTester = new CommandTester($command);

        // act
        $exitCode = $commandTester->execute([]);

        // assert
        self::assertEquals(0, $exitCode);

        // verify only 2 lots opened (third has opensAt in future)
        $query = $this->entityManager->createQuery(
            'SELECT COUNT(l.id) FROM Trade\Domain\Lot\Entity\Lot l WHERE l.status = :status'
        );
        $query->setParameter('status', LotStatusEnum::OPEN);
        $openedCount = $query->getSingleScalarResult();

        self::assertEquals(2, $openedCount);

        // verify lot with future opensAt is still CREATED
        $lotFuture = $this->entityManager->find(Lot::class, new Id(self::LOT_ID_FUTURE));
        self::assertEquals(LotStatusEnum::CREATED, $lotFuture->getStatus());
    }

    public function testAlreadyOpenedLotsAreSkipped(): void
    {
        // arrange - manually open one lot before running command
        $lot1 = $this->entityManager->find(Lot::class, new Id(self::LOT_ID_READY_1));
        $lot1->open();
        $this->entityManager->flush();
        $this->entityManager->clear();

        $command = $this->application->find('trade:open-lots');
        $commandTester = new CommandTester($command);

        // act
        $exitCode = $commandTester->execute([]);

        // assert
        self::assertEquals(0, $exitCode);
        self::assertStringContainsString('Successfully opened 1 lot(s)', $commandTester->getDisplay());

        // verify first lot is still OPEN
        $this->entityManager->clear();
        $lot1 = $this->entityManager->find(Lot::class, new Id(self::LOT_ID_READY_1));
        self::assertEquals(LotStatusEnum::OPEN, $lot1->getStatus());

        // verify second lot was opened by command
        $lot2 = $this->entityManager->find(Lot::class, new Id(self::LOT_ID_READY_2));
        self::assertEquals(LotStatusEnum::OPEN, $lot2->getStatus());
    }

    public function testEventDataContainsCorrectInformation(): void
    {
        // arrange
        $command = $this->application->find('trade:open-lots');
        $commandTester = new CommandTester($command);

        // act
        $commandTester->execute([]);

        // assert
        $envelopes = $this->transport('event.bus')->queue()->messages(LotOpenedEvent::class);
        self::assertCount(2, $envelopes);

        $event = $envelopes[0];
        self::assertInstanceOf(LotOpenedEvent::class, $event);
        self::assertInstanceOf(Id::class, $event->lotId);
        self::assertInstanceOf(\DateTimeImmutable::class, $event->openedAt);
    }
}
