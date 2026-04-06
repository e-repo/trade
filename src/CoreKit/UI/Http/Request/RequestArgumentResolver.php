<?php

declare(strict_types=1);

namespace CoreKit\UI\Http\Request;

use CoreKit\UI\Http\Exception\ViolationException;
use http\Exception\RuntimeException;
use JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use TypeError;

final readonly class RequestArgumentResolver implements ValueResolverInterface
{
    public function __construct(
        private BooleanAttributeDenormalizer $denormalizer,
        private ValidatorInterface $validator,
    ) {}

    /**
     * @throws JsonException
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $payload = [];
        $argumentType = $argument->getType();

        if (! $argumentType || ! is_subclass_of($argumentType, RequestPayloadInterface::class)) {
            return [];
        }

        if (false === empty($request->getContent())) {
            $payload = $request->toArray();
        }

        $payload = array_replace(
            $this->getPayload($request),
            $request->query->all(),
            $request->attributes->get('_route_params'),
            $payload
        );

        $dto = $this->payloadToDto($payload, $argumentType);
        $dto = $this->addRequestFilesToDto($dto, $request);

        $this->checkViolation($dto);

        yield $dto;
    }

    private function addRequestFilesToDto(object $dto, Request $request): object
    {
        if ('form' !== $request->getContentTypeFormat()) {
            return $dto;
        }

        foreach ($request->files->all() as $propertyName => $formData) {
            if (false === property_exists($dto, $propertyName)) {
                continue;
            }

            try {
                $dto->{$propertyName} = $formData;
            } catch (TypeError $exception) {
                $constraintViolation = new ConstraintViolation(
                    message: sprintf('Неверный тип данных у поля для передачи файлов \'%s\'', $propertyName),
                    messageTemplate: '',
                    parameters: [],
                    root: null,
                    propertyPath: null,
                    invalidValue: null
                );

                throw new ViolationException(
                    new ConstraintViolationList([$constraintViolation])
                );
            }
        }

        return $dto;
    }

    private function payloadToDto(array $payload, string $argumentType): mixed
    {
        return $this->denormalizer
            ->denormalize(
                data: $payload,
                type: $argumentType,
                context: [
                    'disable_type_enforcement' => true,
                ]
            );
    }

    private function checkViolation(mixed $dto): void
    {
        $violations = $this->validator->validate($dto);

        if ($violations->count() > 0) {
            throw new ViolationException($violations);
        }
    }

    /**
     * @throws JsonException
     */
    private function getPayload(Request $request): array
    {
        $data = $request->request->get('payload');

        if (! is_string($data)) {
            return [];
        }

        if (! json_validate($data)) {
            return throw new RuntimeException('Некорректные данные запроса.');
        }

        return [
            'payload' => json_decode($data, true, 512, JSON_THROW_ON_ERROR),
        ];
    }
}
