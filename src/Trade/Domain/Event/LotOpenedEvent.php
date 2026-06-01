<?php

declare(strict_types=1);

namespace Trade\Domain\Event;

use CoreKit\Domain\Entity\Id;
use DateTimeImmutable;

final readonly class LotOpenedEvent implements DomainEventInterface
{
    public function __construct(
        public Id $lotId,
        public DateTimeImmutable $openedAt,
    ) {}
}
