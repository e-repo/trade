<?php

declare(strict_types=1);

namespace Trade\Domain\Lot\Enum;

enum LotStatusEnum: string
{
    case CREATED = 'CREATED';
    case OPEN = 'OPEN';
    case CLOSED = 'CLOSED';
}
