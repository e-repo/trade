<?php

declare(strict_types=1);

namespace Trade\Domain\Lot\Enum;

enum CloseReasonEnum: string
{
    case EXPIRED = 'EXPIRED';
    case MANUAL = 'MANUAL';
}
