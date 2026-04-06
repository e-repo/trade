<?php

declare(strict_types=1);

namespace CoreKit\Application\Security;

final readonly class UserIdentity
{
    public function __construct(
        public string $id,
        public string $firstName,
        public string $email,
        public string $role,
        public string $status,
    ) {}
}
