<?php

declare(strict_types=1);

namespace CoreKit\Application\Security;

interface AuthorizationInterface
{
    public function isGranted(mixed $attributes): bool;
}
