<?php

declare(strict_types=1);

namespace CoreKit\Infra\Security;

use CoreKit\Application\Security\AuthenticationInterface;
use CoreKit\Application\Security\UserIdentity;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class AuthenticationService implements AuthenticationInterface
{
    public function __construct(
        private Security $security
    ) {}

    public function getUser(): ?UserIdentity
    {
        $user = $this->security->getUser();

        if (null === $user) {
            return null;
        }

        return new UserIdentity(
            id: $user->id,
            firstName: $user->firstName,
            email: $user->email,
            role: $user->role,
            status: $user->status,
        );
    }
}
