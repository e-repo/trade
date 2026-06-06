<?php

declare(strict_types=1);

namespace Trade\Application\Lot\Command\CloseDueLots;

use Carbon\Carbon;
use CoreKit\Application\Bus\CommandHandlerInterface;
use CoreKit\Application\Bus\EventBusInterface;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Throwable;
use Trade\Domain\Event\LotClosedEvent;
use Trade\Domain\Event\WinnerDeterminatedEvent;
use Trade\Domain\Lot\Enum\CloseReasonEnum;
use Trade\Domain\Lot\Repository\LotRepositoryInterface;

final readonly class Handler implements CommandHandlerInterface
{
    public function __construct(
        private LotRepositoryInterface $lotRepository,
        private EventBusInterface $eventBus,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(Command $command): Result
    {
        $totalProcessed = 0;
        $successfullyClosed = 0;
        $failed = 0;

        foreach ($this->lotRepository->findLotsToCloseIterator($command->now) as $lotData) {
            $totalProcessed++;

            try {
                $lot = $this->lotRepository->lockForUpdate($lotData->lotId);

                $lot->close(CloseReasonEnum::EXPIRED);

                $this->eventBus->publish(
                    new LotClosedEvent(
                        lotId: $lot->getId(),
                        closeReason: CloseReasonEnum::EXPIRED,
                        closedAt: Carbon::now()->toDateTimeImmutable(),
                    )
                );

                if (!empty($lotData->allocatedBids)) {
                    $winners = array_map(
                        fn($bid) => [
                            'bidId' => $bid->bidId->value,
                            'contractorId' => $bid->contractorId->value,
                            'allocatedVolume' => $bid->allocatedVolume,
                            'pricePerTon' => $bid->pricePerTon,
                        ],
                        $lotData->allocatedBids
                    );

                    $this->eventBus->publish(
                        new WinnerDeterminatedEvent(
                            lotId: $lot->getId(),
                            winners: $winners,
                            determinedAt: Carbon::now()->toDateTimeImmutable(),
                        )
                    );
                }

                $successfullyClosed++;
            } catch (Throwable $throwable) {
                $failed++;
                $this->logger->error('Failed to close lot', [
                    'lot_id' => $lotData->lotId->value,
                    'error' => $throwable->getMessage(),
                    'trace' => $throwable->getTraceAsString(),
                ]);
            }
        }

        return new Result(
            totalProcessed: $totalProcessed,
            successfullyClosed: $successfullyClosed,
            failed: $failed,
        );
    }
}
