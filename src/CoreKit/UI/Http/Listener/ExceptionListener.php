<?php

declare(strict_types=1);

namespace CoreKit\UI\Http\Listener;

use CoreKit\UI\Http\Exception\Resolver;
use CoreKit\UI\Http\Exception\Resolver\ExceptionAttributes;
use CoreKit\UI\Http\Exception\ViolationException;
use CoreKit\UI\Http\Response\ResponseFactory;
use CoreKit\UI\Http\Response\Violation;
use CoreKit\UI\Http\Response\Violation\ViolationItem;
use DomainException;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Throwable;

final readonly class ExceptionListener
{
    public function __construct(
        private ResponseFactory $responseFactory,
        private Resolver $exceptionResolver,
        private LoggerInterface $logger,
        private bool $isDebug,
    ) {}

    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        $response = match (get_class($throwable)) {
            ViolationException::class => $this->makeResponseFromViolations($throwable->violations),
            default => $this->resolveResponse($throwable)
        };

        $event->setResponse($response);
    }

    private function makeResponseFromViolations(ConstraintViolationListInterface $violations): JsonResponse
    {
        $violationList = [];

        /** @var ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            $violationList[] = new ViolationItem(
                detail: $violation->getMessage(),
                source: $violation->getPropertyPath()
            );
        }

        return $this->responseFactory->toBadRequestJsonResponse(
            new Violation(
                message: 'Некорректные данные запроса.',
                errors: $violationList
            )
        );
    }

    private function resolveResponse(Throwable $throwable): JsonResponse
    {
        if ($throwable instanceof HandlerFailedException) {
            $throwable = $throwable->getPrevious();
        }

        if ($throwable instanceof AccessDeniedException) {
            $throwable = new AccessDeniedException('Доступ запрещен.');
        }

        $exceptionAttributes = $this->exceptionResolver->resolve(get_class($throwable));

        if (null === $exceptionAttributes) {
            $exceptionAttributes = ExceptionAttributes::fromExceptionCode(
                code: Response::HTTP_INTERNAL_SERVER_ERROR,
                isHidden: ! $this->isDebug
            );
        }

        if (
            $exceptionAttributes->code >= Response::HTTP_INTERNAL_SERVER_ERROR ||
            true === $exceptionAttributes->loggable
        ) {
            $this->logger->error($throwable->getMessage(), [
                'exception' => $throwable,
            ]);
        }

        $message = $exceptionAttributes->hidden
            ? Response::$statusTexts[$exceptionAttributes->code]
            : $throwable->getMessage();

        $exceptionData = $this->getExceptionData($throwable);

        return $this->responseFactory->toJsonResponse(
            data: new Violation(
                message: 'Ошибка бизнес-логики.',
                errors: [
                    new ViolationItem(
                        detail: $message,
                        source: $this->isDebug ? $throwable->getTraceAsString() : '',
                        data: $exceptionData,
                    ),
                ]
            ),
            status: $exceptionAttributes->code
        );
    }

    private function getExceptionData(Throwable $throwable): array
    {
        $exceptionData = [];

        if (! is_subclass_of($throwable, DomainException::class)) {
            return [];
        }

        $reflection = new ReflectionClass($throwable);
        $classProperty = $reflection->getProperties(ReflectionProperty::IS_PRIVATE);

        foreach ($classProperty as $property) {
            $methodName = sprintf('get%s', ucfirst($property->getName()));

            if (! method_exists($throwable, $methodName)) {
                continue;
            }

            $exceptionData[$property->getName()] = $throwable->$methodName();
        }

        return $exceptionData;
    }
}
