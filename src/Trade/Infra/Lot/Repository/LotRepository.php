<?php

declare(strict_types=1);

namespace Trade\Infra\Lot\Repository;

use CoreKit\Domain\Entity\Id;
use CoreKit\Domain\Exception\NotFoundException;
use DateTimeImmutable;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Trade\Domain\Lot\Entity\Lot;
use Trade\Domain\Lot\Enum\LotStatusEnum;
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

    public function lockForUpdate(Id $id): Lot
    {
        $lot = $this->em->find(
            Lot::class,
            $id,
            LockMode::PESSIMISTIC_WRITE
        );

        if ($lot === null) {
            throw new NotFoundException('Lot not found');
        }

        return $lot;
    }

    public function findLotsToOpen(DateTimeImmutable $now): array
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('l')
            ->from(Lot::class, 'l')
            ->where('l.status = :status')
            ->andWhere('l.opensAt <= :now')
            ->setParameter('status', LotStatusEnum::CREATED)
            ->setParameter('now', $now);

        return $qb->getQuery()->getResult();
    }
}
