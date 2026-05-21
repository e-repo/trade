<?php

declare(strict_types=1);

namespace Trade\Infra\Lot\Repository;

use CoreKit\Domain\Entity\Id;
use CoreKit\Domain\Exception\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Trade\Domain\Lot\Entity\Lot;
use Trade\Domain\Lot\Repository\LotRepositoryInterface;

final class LotRepository implements LotRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function add(Lot $lot): void
    {
        $this->em->persist($lot);
    }

    public function get(Id $id): Lot
    {
        $lot = $this->em->find(Lot::class, $id);

        if ($lot === null) {
            throw new NotFoundException('Lot not found');
        }

        return $lot;
    }
}
