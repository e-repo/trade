<?php

declare(strict_types=1);

namespace CoreKit\UI\Http\Exception;

use CoreKit\UI\Http\Exception\Resolver\ExceptionAttributes;
use InvalidArgumentException;

final class Resolver
{
    private array $exceptionMap;

    public function __construct(array $exceptions)
    {
        foreach ($exceptions as $class => $params) {
            if (empty($params['code'])) {
                throw new InvalidArgumentException();
            }

            $this->addToExceptionMap(
                class: $class,
                code: $params['code'],
                hidden: $params['hidden'] ?? true,
                loggable: $params['loggable'] ?? true,
            );
        }
    }

    public function resolve(string $throwableClass): ?ExceptionAttributes
    {
        foreach ($this->exceptionMap as $class => $attribute) {
            if ($throwableClass === $class || is_subclass_of($throwableClass, $class)) {
                return $attribute;
            }
        }

        return null;
    }

    private function addToExceptionMap(
        string $class,
        int $code,
        bool $hidden,
        bool $loggable
    ): void {
        $this->exceptionMap[$class] = new ExceptionAttributes($code, $hidden, $loggable);
    }
}
