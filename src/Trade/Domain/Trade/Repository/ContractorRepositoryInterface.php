<?php

declare(strict_types=1);

namespace Trade\Domain\Trade\Repository;

use CoreKit\Domain\ValueObject\Id;
use Trade\Domain\Trade\Entity\Contractor;

interface ContractorRepositoryInterface
{
    public function get(Id $id): Contractor;
}
