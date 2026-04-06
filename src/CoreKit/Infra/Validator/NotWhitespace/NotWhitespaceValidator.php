<?php

declare(strict_types=1);

namespace CoreKit\Infra\Validator\NotWhitespace;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class NotWhitespaceValidator extends ConstraintValidator
{
    public function __construct() {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (! $constraint instanceof NotWhitespace) {
            throw new UnexpectedTypeException($constraint, NotWhitespace::class);
        }

        if (! is_string($value)) {
            return;
        }

        if ('' === $value) {
            return;
        }

        if ('' === trim($value)) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ propertyPath }}', $this->context->getPropertyPath())
                ->addViolation();
        }
    }
}
