<?php

declare(strict_types=1);

namespace CoreKit\Infra;

use CoreKit\Application\Bus\EventBusInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

final class EventBus implements EventBusInterface
{
    public function __construct(
        private MessageBusInterface $eventBus,
    ) {}

    public function publish(object $event): void
    {
        $envelope = (new Envelope($event))
            ->with(
                new DispatchAfterCurrentBusStamp()
            );

        $this->eventBus->dispatch($envelope);
    }
}
