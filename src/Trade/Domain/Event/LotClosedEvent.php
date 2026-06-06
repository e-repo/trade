<?php

declare(strict_types=1);

namespace Trade\Domain\Event;

use CoreKit\Domain\Entity\Id;
use DateTimeImmutable;
use Trade\Domain\Lot\Enum\CloseReasonEnum;

final readonly class LotClosedEvent implements DomainEventInterface
{
    public function __construct(
        public Id $lotId,
        public CloseReasonEnum $closeReason,
        public DateTimeImmutable $closedAt,
    ) {}
}
