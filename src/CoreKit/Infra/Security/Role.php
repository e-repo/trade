<?php

declare(strict_types=1);

namespace CoreKit\Infra\Security;

enum Role: string
{
    case USER = 'ROLE_USER';

    case ADMIN = 'ROLE_ADMIN';
}
