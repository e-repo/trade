<?php

declare(strict_types=1);

namespace Trade\Application\Listener;

use Carbon\Carbon;
use CoreKit\Application\Bus\EventListenerInterface;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Trade\Domain\Event\DomainEventInterface;

final readonly class EventLoggerListener implements EventListenerInterface
{
    public function __construct(
        private LoggerInterface $eventLogger,
        private SerializerInterface $serializer,
    ) {}

    public function __invoke(DomainEventInterface $event): void
    {
        $this->eventLogger->info('Domain event occurred', [
            'event_type' => $event::class,
            'event_data' => $this->serializer->normalize($event),
            'occurred_at' => Carbon::now()->format(DateTimeImmutable::ATOM),
        ]);
    }
}
