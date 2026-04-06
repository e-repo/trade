<?php

declare(strict_types=1);

namespace Trade\Domain\Trade\Repository;

use CoreKit\Domain\ValueObject\Id;
use Trade\Domain\Trade\Entity\CargoType;

interface CargoTypeRepositoryInterface
{
    public function get(Id $id): CargoType;
}
