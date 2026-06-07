<?php

declare(strict_types=1);

namespace Trade\Domain\Lot\Repository;

use CoreKit\Domain\Entity\Id;
use DateTimeImmutable;
use Trade\Domain\Lot\Entity\Lot;

interface LotRepositoryInterface
{
    public function add(Lot $lot): void;

    public function get(Id $id): Lot;

    public function lockForUpdate(Id $id): Lot;

    /**
     * @return array<Lot>
     */
    public function findLotsToOpen(DateTimeImmutable $now): array;

    /**
     * Итератор для батчевой обработки лотов, подлежащих закрытию.
     * Каждая итерация возвращает DTO с лотом и его выделенными ставками.
     *
     * @param DateTimeImmutable $now
     * @param int $batchSize
     * @return \Generator<LotWithAllocatedBidsDto>
     */
    public function findLotsToCloseIterator(DateTimeImmutable $now, int $batchSize = 100): \Generator;

    public function getLotDetails(Id $lotId): LotDetailsDto;
}
