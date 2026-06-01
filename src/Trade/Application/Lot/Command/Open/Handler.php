<?php

declare(strict_types=1);

namespace Trade\Application\Lot\Command\Open;

use CoreKit\Application\Bus\CommandHandlerInterface;
use CoreKit\Application\Bus\EventBusInterface;
use CoreKit\Domain\Entity\Id;
use DateTimeImmutable;
use Trade\Domain\Event\LotOpenedEvent;
use Trade\Domain\Lot\Repository\LotRepositoryInterface;

final readonly class Handler implements CommandHandlerInterface
{
    public function __construct(
        private LotRepositoryInterface $lotRepository,
        private EventBusInterface $eventBus,
    ) {}

    public function __invoke(Command $command): void
    {
        $lot = $this->lotRepository->get(new Id($command->lotId));

        $lot->open();

        $this->eventBus->publish(
            new LotOpenedEvent(
                lotId: $lot->getId(),
                openedAt: new DateTimeImmutable(),
            )
        );
    }
}
