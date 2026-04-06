<?php

declare(strict_types=1);

namespace CoreKit\UI\Http\Exception;

use InvalidArgumentException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final class ViolationException extends InvalidArgumentException
{
    public function __construct(
        public readonly ConstraintViolationListInterface $violations
    ) {
        parent::__construct('Некорректные данные запроса.');
    }
}
