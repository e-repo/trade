<?php

declare(strict_types=1);

namespace CoreKit\Domain\Entity;

interface SpecificationInterface
{
    public function isSatisfiedBy(mixed $candidate): bool;
}
