<?php

declare(strict_types=1);

namespace Trade\Domain\Event;

use CoreKit\Domain\Entity\Id;
use DateTimeImmutable;

final readonly class WinnerDeterminatedEvent implements DomainEventInterface
{
    /**
     * @param Id $lotId
     * @param array<array{bidId: string, contractorId: string, allocatedVolume: int, pricePerTon: int}> $winners
     * @param DateTimeImmutable $determinedAt
     */
    public function __construct(
        public Id $lotId,
        public array $winners,
        public DateTimeImmutable $determinedAt,
    ) {}
}
