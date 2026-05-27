<?php

declare(strict_types=1);

namespace Trade\Domain\Lot\Enum;

enum BidStatusEnum: string
{
    case PENDING = 'PENDING';
    case ACTIVE = 'ACTIVE';
    case PARTIALLY_ACTIVE = 'PARTIALLY_ACTIVE';
    case OUTBID = 'OUTBID';
    case REJECTED = 'REJECTED';
}
