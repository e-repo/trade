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
use Trade\Domain\Lot\Repository\AllocatedBidDto;
use Trade\Domain\Lot\Repository\LotRepositoryInterface;
use Trade\Domain\Lot\Repository\LotWithAllocatedBidsDto;

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

    public function findLotsToCloseIterator(DateTimeImmutable $now, int $batchSize = 100): \Generator
    {
        $connection = $this->em->getConnection();
        $offset = 0;

        while (true) {
            // Оптимизированный SQL: только нужные поля
            $sql = <<<SQL
                SELECT
                    l.id as lot_id,
                    b.id as bid_id,
                    b.contractor_id,
                    b.allocated_volume,
                    b.price_per_ton
                FROM trade.lot l
                LEFT JOIN trade.bid b ON b.lot_id = l.id AND b.allocated_volume > 0
                WHERE l.status = :status
                  AND l.termination_closes_at <= :now
                ORDER BY l.id, b.price_per_ton ASC, b.created_at ASC
                LIMIT :limit OFFSET :offset
            SQL;

            $stmt = $connection->executeQuery(
                $sql,
                [
                    'status' => LotStatusEnum::OPEN->value,
                    'now' => $now->format('Y-m-d H:i:s'),
                    'limit' => $batchSize,
                    'offset' => $offset,
                ]
            );

            $results = $stmt->fetchAllAssociative();

            if (empty($results)) {
                break;
            }

            // Группируем результаты по лотам
            $lotsData = [];
            foreach ($results as $row) {
                $lotId = $row['lot_id'];

                if (!isset($lotsData[$lotId])) {
                    $lotsData[$lotId] = [
                        'lotId' => new Id($lotId),
                        'bids' => [],
                    ];
                }

                // Если есть ставка (LEFT JOIN может вернуть NULL)
                if ($row['bid_id'] !== null) {
                    $lotsData[$lotId]['bids'][] = new AllocatedBidDto(
                        bidId: new Id($row['bid_id']),
                        contractorId: new Id($row['contractor_id']),
                        allocatedVolume: (int) $row['allocated_volume'],
                        pricePerTon: (int) $row['price_per_ton'],
                    );
                }
            }

            foreach ($lotsData as $data) {
                yield new LotWithAllocatedBidsDto(
                    lotId: $data['lotId'],
                    allocatedBids: $data['bids'],
                );
            }

            $offset += $batchSize;
        }
    }
}
