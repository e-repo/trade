<?php

declare(strict_types=1);

namespace Trade\Domain\Lot\Repository;

use CoreKit\Domain\Entity\Id;
use Trade\Domain\Lot\Entity\Lot;

interface LotRepositoryInterface
{
    public function add(Lot $lot): void;

    public function get(Id $id): Lot;
}
