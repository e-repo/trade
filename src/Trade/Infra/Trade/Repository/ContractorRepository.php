<?php

declare(strict_types=1);

namespace Trade\Infra\Trade\Repository;

use CoreKit\Domain\Exception\NotFoundException;
use CoreKit\Domain\ValueObject\Id;
use Doctrine\ORM\EntityManagerInterface;
use Trade\Domain\Trade\Entity\Contractor;
use Trade\Domain\Trade\Repository\ContractorRepositoryInterface;

final class ContractorRepository implements ContractorRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function get(Id $id): Contractor
    {
        $contractor = $this->em->find(Contractor::class, $id);

        if ($contractor === null) {
            throw new NotFoundException('Contractor not found');
        }

        return $contractor;
    }
}
