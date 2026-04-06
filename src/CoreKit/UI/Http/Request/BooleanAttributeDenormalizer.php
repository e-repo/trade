<?php

declare(strict_types=1);

namespace CoreKit\UI\Http\Request;

use CoreKit\UI\Http\Exception\ViolationException;
use ReflectionClass;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

final class BooleanAttributeDenormalizer
{
    private const ALLOWED_BOOLEAN_STRINGS = ['', '0', 'false', '1', 'true'];

    public function __construct(
        private DenormalizerInterface $denormalizer,
    ) {}

    /**
     * @throws ExceptionInterface
     */
    public function denormalize(mixed $data, string $type, array $context = []): mixed
    {
        $payload = $this->castBooleanFields($type, $data);

        return $this->denormalizer
            ->denormalize(
                data: $payload,
                type: $type,
                context: $context
            );
    }

    private function castBooleanFields(string $className, array $data): array
    {
        $dto = new $className();
        $reflection = new ReflectionClass($dto);

        foreach ($reflection->getProperties() as $property) {
            $propertyName = $property->getName();

            if (false === isset($data[$propertyName])) {
                continue;
            }

            $propertyType = $property->getType()?->getName();

            if ('bool' !== $propertyType) {
                continue;
            }

            $propertyValue = $data[$propertyName];
            $isPropertyAllowsNull = $property->getType()?->allowsNull();

            if (true === is_bool($propertyValue)) {
                continue;
            }

            if (null === $propertyValue && $isPropertyAllowsNull) {
                $dto->$propertyName = null;
                continue;
            }

            if (true === is_array($propertyValue)) {
                $this->castBooleanFields($propertyType, $propertyValue);
            }

            if (false === in_array($propertyValue, self::ALLOWED_BOOLEAN_STRINGS, true)) {
                $violations = new ConstraintViolationList([
                    $this->createConstraintViolation(
                        message: sprintf("Ошибка определения значения для '%s'", $propertyName),
                    ),
                ]);

                throw new ViolationException($violations);
            }

            $data[$propertyName] = ('true' === $propertyValue) || ('1' === $propertyValue);
        }

        return $data;
    }

    private function createConstraintViolation(
        $message,
        $propertyPath = null,
    ): ConstraintViolation {
        return new ConstraintViolation(
            message: $message,
            messageTemplate: '',
            parameters: [],
            root: null,
            propertyPath: $propertyPath,
            invalidValue: null
        );
    }
}
