<?php

declare(strict_types=1);

namespace Trade\Domain\Dictionary\Repository;

use CoreKit\Domain\Entity\Id;
use Trade\Domain\Dictionary\Entity\VolumeStep;

interface VolumeStepRepositoryInterface
{
    public function get(Id $id): VolumeStep;
}
