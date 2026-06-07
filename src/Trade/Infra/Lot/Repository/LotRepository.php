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
use Trade\Domain\Lot\Repository\LotDetailsDto;
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
            // Шаг 1: Получаем ID лотов для закрытия с блокировкой
            $lotIdsSql = <<<SQL
                SELECT l.id
                FROM trade.lot l
                WHERE l.status = :status
                  AND l.termination_closes_at <= :now
                ORDER BY l.id
                LIMIT :limit OFFSET :offset
                FOR UPDATE
            SQL;

            $stmt = $connection->executeQuery(
                $lotIdsSql,
                [
                    'status' => LotStatusEnum::OPEN->value,
                    'now' => $now->format('Y-m-d H:i:s'),
                    'limit' => $batchSize,
                    'offset' => $offset,
                ]
            );

            $lotIds = array_column($stmt->fetchAllAssociative(), 'id');

            if (empty($lotIds)) {
                break;
            }

            // Шаг 2: Загружаем заблокированные Lot entity через Doctrine
            $lotQueryBuilder = $this->em->createQueryBuilder();
            $lots = $lotQueryBuilder->select('l')
                ->from(Lot::class, 'l')
                ->where($lotQueryBuilder->expr()->in('l.id', ':ids'))
                ->setParameter('ids', $lotIds)
                ->getQuery()
                ->getResult();

            // Создаём индекс лотов по ID для O(1) доступа
            $lotsById = [];
            foreach ($lots as $lot) {
                $lotsById[$lot->getId()->value] = $lot;
            }

            // Шаг 3: Получаем allocated bids для этих лотов одним запросом
            $bidsSql = <<<SQL
                SELECT
                    b.id as bid_id,
                    b.lot_id,
                    b.contractor_id,
                    b.allocated_volume,
                    b.price_per_ton
                FROM trade.bid b
                WHERE b.lot_id IN (:lot_ids)
                  AND b.allocated_volume > 0
                ORDER BY b.lot_id, b.price_per_ton ASC, b.created_at ASC
            SQL;

            $stmt = $connection->executeQuery(
                $bidsSql,
                ['lot_ids' => $lotIds],
                ['lot_ids' => \Doctrine\DBAL\ArrayParameterType::STRING]
            );

            $bidsResults = $stmt->fetchAllAssociative();

            // Группируем ставки по лотам
            $bidsByLotId = [];
            foreach ($bidsResults as $row) {
                $lotId = $row['lot_id'];

                if (!isset($bidsByLotId[$lotId])) {
                    $bidsByLotId[$lotId] = [];
                }

                $bidsByLotId[$lotId][] = new AllocatedBidDto(
                    bidId: new Id($row['bid_id']),
                    contractorId: new Id($row['contractor_id']),
                    allocatedVolume: (int) $row['allocated_volume'],
                    pricePerTon: (int) $row['price_per_ton'],
                );
            }

            // Шаг 4: Возвращаем DTO с заблокированными лотами и их ставками
            foreach ($lotIds as $lotId) {
                yield new LotWithAllocatedBidsDto(
                    lot: $lotsById[$lotId],
                    allocatedBids: $bidsByLotId[$lotId] ?? [],
                );
            }

            $offset += $batchSize;
        }
    }

    public function getLotDetails(Id $lotId): LotDetailsDto
    {
        $connection = $this->em->getConnection();

        $sql = <<<SQL
            SELECT
                l.id as lot_id,
                l.status,
                l.volume_total_volume as total_volume,
                l.price_start_price as start_price,
                l.price_price_step as price_step,
                l.opens_at,
                l.termination_closes_at,
                l.termination_close_reason as close_reason,
                COALESCE(
                    json_agg(DISTINCT b.contractor_id) FILTER (WHERE b.allocated_volume > 0),
                    '[]'
                ) as winner_contractor_ids
            FROM trade.lot l
            LEFT JOIN trade.bid b ON b.lot_id = l.id
            WHERE l.id = :lot_id
            GROUP BY l.id
        SQL;

        $result = $connection->fetchAssociative($sql, [
            'lot_id' => $lotId->value,
        ]);

        if ($result === false) {
            throw new NotFoundException('Lot not found');
        }

        $winnerContractorIdsRaw = json_decode($result['winner_contractor_ids'], true);
        $winnerContractorIds = array_map(
            fn(string $id) => new Id($id),
            $winnerContractorIdsRaw
        );

        return new LotDetailsDto(
            lotId: new Id($result['lot_id']),
            status: $result['status'],
            totalVolume: (int) $result['total_volume'],
            startPrice: (int) $result['start_price'],
            priceStep: (int) $result['price_step'],
            opensAt: new \DateTimeImmutable($result['opens_at']),
            closesAt: new \DateTimeImmutable($result['termination_closes_at']),
            closeReason: $result['close_reason'],
            winnerContractorIds: $winnerContractorIds,
        );
    }
}
