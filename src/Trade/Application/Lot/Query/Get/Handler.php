<?php

declare(strict_types=1);

namespace Trade\Application\Lot\Query\Get;

use CoreKit\Application\Bus\QueryHandlerInterface;
use Trade\Domain\Lot\Repository\LotRepositoryInterface;

final readonly class Handler implements QueryHandlerInterface
{
    public function __construct(
        private LotRepositoryInterface $lotRepository,
    ) {}

    public function __invoke(Query $query): Result
    {
        $lotDetails = $this->lotRepository->getLotDetails($query->lotId);

        $winnerContractorIds = array_map(
            fn($id) => $id->value,
            $lotDetails->winnerContractorIds
        );

        return new Result(
            lotId: $lotDetails->lotId->value,
            status: $lotDetails->status,
            totalVolume: $lotDetails->totalVolume,
            startPrice: $lotDetails->startPrice,
            priceStep: $lotDetails->priceStep,
            opensAt: $lotDetails->opensAt,
            closesAt: $lotDetails->closesAt,
            closeReason: $lotDetails->closeReason,
            winnerContractorIds: $winnerContractorIds,
        );
    }
}
