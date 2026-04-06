<?php

declare(strict_types=1);

namespace CoreKit\Infra\Validator\NotWhitespace;

use Attribute;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[Attribute]
final class NotWhitespace extends Constraint
{
    public string $message = 'Поле \'{{ propertyPath }}\' не может состоять только из пробелов.';

    #[HasNamedArguments]
    public function __construct(
        string $message = null,
        array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct([], $groups, $payload);

        $this->message = $message ?? $this->message;
    }
}
