<?php

declare(strict_types=1);

namespace Trade\Domain\Lot\ValueObject;

use Doctrine\ORM\Mapping as ORM;
use DomainException;

#[ORM\Embeddable]
class Volume
{
    private const int MAX_VOLUME = 100_000;

    #[ORM\Column]
    private int $totalVolume;

    #[ORM\Column]
    private int $reservedVolume = 0;

    public function __construct(int $totalVolume, int $volumeStepValue)
    {
        $this->validateVolume($totalVolume, $volumeStepValue);
        $this->totalVolume = $totalVolume;
    }

    public function getTotalVolume(): int
    {
        return $this->totalVolume;
    }

    public function getReservedVolume(): int
    {
        return $this->reservedVolume;
    }

    public function getFreeVolume(): int
    {
        return $this->totalVolume - $this->reservedVolume;
    }

    public function reserve(int $amount): void
    {
        if ($this->reservedVolume + $amount > $this->totalVolume) {
            throw new DomainException('Cannot reserve more than total volume');
        }

        if ($amount <= 0) {
            throw new DomainException('Reserve amount must be positive');
        }

        $this->reservedVolume += $amount;
    }

    private function validateVolume(int $totalVolume, int $volumeStepValue): void
    {
        if ($totalVolume <= 0) {
            throw new DomainException('Total volume must be positive');
        }

        if ($totalVolume > self::MAX_VOLUME) {
            throw new DomainException(sprintf('Total volume cannot exceed %d tons', self::MAX_VOLUME));
        }

        if ($totalVolume % $volumeStepValue !== 0) {
            throw new DomainException(sprintf('Total volume must be multiple of volume step (%d tons)', $volumeStepValue));
        }
    }
}
