<?php

declare(strict_types=1);

namespace Trade\Application\Lot\Command\Create;

use CoreKit\Application\Bus\CommandHandlerInterface;
use CoreKit\Domain\Entity\Id;
use Trade\Domain\Dictionary\Repository\CargoTypeRepositoryInterface;
use Trade\Domain\Dictionary\Repository\VolumeStepRepositoryInterface;
use Trade\Domain\Lot\Entity\Lot;
use Trade\Domain\Lot\Repository\LotRepositoryInterface;

final readonly class Handler implements CommandHandlerInterface
{
    public function __construct(
        private LotRepositoryInterface $lotRepository,
        private CargoTypeRepositoryInterface $cargoTypeRepository,
        private VolumeStepRepositoryInterface $volumeStepRepository,
    ) {}

    public function __invoke(Command $command): Result
    {
        $cargoType = $this->cargoTypeRepository->get(new Id($command->cargoTypeId));
        $volumeStep = $this->volumeStepRepository->get(new Id($command->volumeStepId));

        $lot = new Lot(
            cargoType: $cargoType,
            totalVolume: $command->totalVolume,
            startPrice: $command->startPrice,
            priceStep: $command->priceStep,
            volumeStep: $volumeStep,
            opensAt: $command->opensAt,
            closesAt: $command->closesAt,
        );

        $this->lotRepository->add($lot);

        return new Result(
            lotId: $lot->getId()->getValue(),
            status: $lot->getStatus()->value,
        );
    }
}
