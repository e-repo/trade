<?php

declare(strict_types=1);

namespace CoreKit\Infra\Security;

use CoreKit\Application\Security\AuthorizationInterface;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class AuthorizationService implements AuthorizationInterface
{
    public function __construct(
        private Security $security
    ) {}

    public function isGranted(mixed $attributes): bool
    {
        return $this->security->isGranted($attributes);
    }
}
