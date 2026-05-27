<?php

declare(strict_types=1);

namespace Trade\Infra\Lot\Repository;

use CoreKit\Domain\Entity\Id;
use CoreKit\Domain\Exception\NotFoundException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Trade\Domain\Lot\Collection\BidCollection;
use Trade\Domain\Lot\Entity\Bid;
use Trade\Domain\Lot\Repository\BidRepositoryInterface;

final readonly class BidRepository implements BidRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function add(Bid $bid): void
    {
        $this->em->persist($bid);
    }

    public function get(Id $id): Bid
    {
        $bid = $this->em->find(Bid::class, $id);

        if ($bid === null) {
            throw new NotFoundException('Bid not found');
        }

        return $bid;
    }

    public function findActiveBidsForLotWithLock(Id $lotId): BidCollection
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('b')
            ->from(Bid::class, 'b')
            ->where('b.lot = :lotId')
            ->andWhere('b.allocatedVolume > 0')
            ->setParameter('lotId', $lotId);

        $query = $qb->getQuery();
        $query->setLockMode(LockMode::PESSIMISTIC_WRITE);

        $bids = $query->getResult();

        return new BidCollection(...$bids);
    }
}
