<?php

declare(strict_types=1);

namespace Trade\Application\Lot\Command\OpenDueLots;

use CoreKit\Application\Bus\CommandBusInterface;
use CoreKit\Application\Bus\CommandHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Trade\Application\Lot\Command\Open;
use Trade\Domain\Lot\Repository\LotRepositoryInterface;

final readonly class Handler implements CommandHandlerInterface
{
    public function __construct(
        private LotRepositoryInterface $lotRepository,
        private CommandBusInterface $commandBus,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(Command $command): Result
    {
        $lots = $this->lotRepository->findLotsToOpen($command->now);
        $totalProcessed = count($lots);
        $successfullyOpened = 0;
        $failed = 0;

        foreach ($lots as $lot) {
            try {
                $this->commandBus->dispatch(
                    new Open\Command(
                        lotId: $lot->getId()->value,
                    )
                );

                $successfullyOpened++;
            } catch (Throwable $throwable) {
                $failed++;
                $this->logger->error('Failed to open lot', [
                    'lot_id' => $lot->getId()->value,
                    'error' => $throwable->getMessage(),
                ]);
            }
        }

        return new Result(
            totalProcessed: $totalProcessed,
            successfullyOpened: $successfullyOpened,
            failed: $failed,
        );
    }
}
