<?php

declare(strict_types=1);

namespace Trade\Infra\Trade\Repository;

use CoreKit\Domain\Exception\NotFoundException;
use CoreKit\Domain\ValueObject\Id;
use Doctrine\ORM\EntityManagerInterface;
use Trade\Domain\Trade\Entity\VolumeStep;
use Trade\Domain\Trade\Repository\VolumeStepRepositoryInterface;

final class VolumeStepRepository implements VolumeStepRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function get(Id $id): VolumeStep
    {
        $volumeStep = $this->em->find(VolumeStep::class, $id);

        if ($volumeStep === null) {
            throw new NotFoundException('VolumeStep not found');
        }

        return $volumeStep;
    }
}
