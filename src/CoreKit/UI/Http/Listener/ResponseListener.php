<?php

declare(strict_types=1);

namespace CoreKit\UI\Http\Listener;

use CoreKit\UI\Http\Response\ResponseFactory;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;

final readonly class ResponseListener
{
    public function __construct(
        private ResponseFactory $responseFactory,
    ) {}

    public function __invoke(ViewEvent $event): void
    {
        $value = $event->getControllerResult();

        if ($value instanceof Response) {
            return;
        }

        $event->setResponse(
            $this->responseFactory->toJsonResponse(
                $value
            )
        );
    }
}
