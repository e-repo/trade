<?php

declare(strict_types=1);

namespace CoreKit\Domain\Entity;

use CoreKit\Domain\Event\DomainEventInterface;

trait EventRecordTrait
{
    /** @var DomainEventInterface[] */
    private array $events = [];

    /**
     * @return DomainEventInterface[]
     */
    public function getRecordedEvents(): array
    {
        return $this->events;
    }

    public function clearRecordedEvents(): void
    {
        $this->events = [];
    }

    public function record(DomainEventInterface $event): void
    {
        $this->events[] = $event;
    }
}
