<?php

declare(strict_types=1);

namespace Trade\Domain\Lot\ValueObject;

use Doctrine\ORM\Mapping as ORM;
use DomainException;

#[ORM\Embeddable]
class Price
{
    #[ORM\Column]
    private int $startPrice;

    #[ORM\Column]
    private int $priceStep;

    public function __construct(int $startPrice, int $priceStep)
    {
        $this->validatePrice($startPrice, $priceStep);
        $this->startPrice = $startPrice;
        $this->priceStep = $priceStep;
    }

    public function getStartPrice(): int
    {
        return $this->startPrice;
    }

    public function getPriceStep(): int
    {
        return $this->priceStep;
    }

    private function validatePrice(int $startPrice, int $priceStep): void
    {
        if ($startPrice <= 0) {
            throw new DomainException('Start price must be positive');
        }

        if ($priceStep <= 0) {
            throw new DomainException('Price step must be positive');
        }
    }
}
