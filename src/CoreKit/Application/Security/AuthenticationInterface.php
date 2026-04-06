<?php

declare(strict_types=1);

namespace CoreKit\Application\Security;

interface AuthenticationInterface
{
    public function getUser(): ?UserIdentity;
}
