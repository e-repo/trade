<?php

declare(strict_types=1);

namespace CoreKit\Infra;

use CoreKit\Application\Bus\QueryBusInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

final class QueryBus implements QueryBusInterface
{
    use HandleTrait;

    public function __construct(
        private readonly MessageBusInterface $queryBus,
    ) {
        $this->messageBus = $this->queryBus;
    }

    /**
     * @throws Throwable
     */
    public function dispatch(object $message): mixed
    {
        try {
            return $this->handle($message);
        } catch (HandlerFailedException $e) {
            throw $e->getPrevious();
        }
    }
}
