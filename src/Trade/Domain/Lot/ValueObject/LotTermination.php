<?php

declare(strict_types=1);

namespace Trade\Domain\Lot\ValueObject;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Trade\Domain\Lot\Enum\CloseReasonEnum;

#[ORM\Embeddable]
class LotTermination
{
    #[ORM\Column]
    private DateTimeImmutable $closesAt;

    #[ORM\Column(enumType: CloseReasonEnum::class, nullable: true)]
    private ?CloseReasonEnum $closeReason = null;

    public function __construct(DateTimeImmutable $closesAt)
    {
        $this->closesAt = $closesAt;
    }

    public function getClosesAt(): DateTimeImmutable
    {
        return $this->closesAt;
    }

    public function getCloseReason(): ?CloseReasonEnum
    {
        return $this->closeReason;
    }

    public function close(CloseReasonEnum $reason): void
    {
        $this->closeReason = $reason;
    }
}
