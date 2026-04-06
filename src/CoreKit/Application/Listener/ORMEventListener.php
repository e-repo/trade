<?php

declare(strict_types=1);

namespace CoreKit\Application\Listener;

use CoreKit\Application\Bus\EventBusInterface;
use CoreKit\Domain\Entity\HasEventsInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\PersistentCollection;

#[AsDoctrineListener(event: Events::onFlush)]
final readonly class ORMEventListener
{
    public function __construct(
        private EventBusInterface $eventBus,
    ) {}

    public function onFlush(OnFlushEventArgs $args): void
    {
        $this->sendDomainEvents($args);
    }

    public function sendDomainEvents(OnFlushEventArgs $args): void
    {
        $uow = $args->getObjectManager()->getUnitOfWork();

        $sources = [
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates(),
            $uow->getScheduledEntityDeletions(),
        ];

        foreach ($sources as $source) {
            foreach ($source as $entity) {
                if (false === $entity instanceof HasEventsInterface) {
                    continue;
                }

                $this->sendRecordedEvents($entity);
            }
        }

        // ToDo: проверить багу двойной отправки
        $collectionSources = [
            $uow->getScheduledCollectionDeletions(),
            $uow->getScheduledCollectionUpdates(),
        ];

        foreach ($collectionSources as $source) {
            /** @var PersistentCollection $collection */
            foreach ($source as $collection) {
                $entity = $collection->getOwner();

                if (false === $entity instanceof HasEventsInterface) {
                    continue;
                }

                $this->sendRecordedEvents($entity);
            }
        }
    }

    private function sendRecordedEvents(HasEventsInterface $entity): void
    {
        $entityEvents = $entity->getRecordedEvents();
        $entity->clearRecordedEvents();

        foreach ($entityEvents as $event) {
            $this->eventBus->publish($event);
        }
    }
}
