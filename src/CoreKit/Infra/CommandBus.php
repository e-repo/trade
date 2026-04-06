<?php

declare(strict_types=1);

namespace CoreKit\Infra;

use CoreKit\Application\Bus\CommandBusInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

final class CommandBus implements CommandBusInterface
{
    use HandleTrait;

    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {
        $this->messageBus = $this->commandBus;
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
