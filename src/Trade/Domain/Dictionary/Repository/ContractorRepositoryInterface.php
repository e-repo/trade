<?php

declare(strict_types=1);

namespace Trade\Domain\Dictionary\Repository;

use CoreKit\Domain\Entity\Id;
use Trade\Domain\Dictionary\Entity\Contractor;

interface ContractorRepositoryInterface
{
    public function get(Id $id): Contractor;
}
