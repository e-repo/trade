<?php

declare(strict_types=1);

namespace Trade\Application\Bid\Command\PlaceBid;

use CoreKit\Application\Bus\CommandHandlerInterface;
use CoreKit\Domain\Entity\Id;
use Trade\Domain\Dictionary\Repository\ContractorRepositoryInterface;
use Trade\Domain\Lot\Entity\Bid;
use Trade\Domain\Lot\Repository\BidRepositoryInterface;
use Trade\Domain\Lot\Repository\LotRepositoryInterface;
use Trade\Domain\Lot\Strategy\BidAllocationStrategyInterface;

final readonly class Handler implements CommandHandlerInterface
{
    public function __construct(
        private LotRepositoryInterface $lotRepository,
        private BidRepositoryInterface $bidRepository,
        private ContractorRepositoryInterface $contractorRepository,
        private BidAllocationStrategyInterface $allocationStrategy,
    ) {}

    public function __invoke(Command $command): Result
    {
        $lot = $this->lotRepository->lockForUpdate(new Id($command->lotId));

        $existingBids = $this->bidRepository->findActiveBidsForLotWithLock($lot->getId());

        $contractor = $this->contractorRepository->get(new Id($command->contractorId));

        $newBid = Bid::createPending(
            lot: $lot,
            contractor: $contractor,
            requestedVolume: $command->requestedVolume,
            pricePerTon: $command->pricePerTon,
        );

        $result = $lot->placeBid(
            existingBids: $existingBids,
            newBid: $newBid,
            strategy: $this->allocationStrategy,
        );

        $this->bidRepository->add($result->newBid);

        foreach ($result->modifiedBids as $bid) {
            $this->bidRepository->add($bid);
        }

        return new Result(
            bidId: $result->newBid->getId()->value,
            status: $result->newBid->getStatus()->value,
            allocatedVolume: $result->newBid->getAllocatedVolume(),
            requestedVolume: $result->newBid->getRequestedVolume(),
        );
    }
}
