<?php

declare(strict_types=1);

namespace Trade\Infra\Dictionary\Repository;

use CoreKit\Domain\Exception\NotFoundException;
use CoreKit\Domain\Entity\Id;
use Doctrine\ORM\EntityManagerInterface;
use Trade\Domain\Dictionary\Entity\CargoType;
use Trade\Domain\Dictionary\Repository\CargoTypeRepositoryInterface;

final class CargoTypeRepository implements CargoTypeRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function get(Id $id): CargoType
    {
        $cargoType = $this->em->find(CargoType::class, $id);

        if ($cargoType === null) {
            throw new NotFoundException('CargoType not found');
        }

        return $cargoType;
    }
}
