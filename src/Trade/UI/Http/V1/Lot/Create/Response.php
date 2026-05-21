<?php

declare(strict_types=1);

namespace Trade\UI\Http\V1\Lot\Create;

use CoreKit\UI\Http\Response\ResponseInterface;

final readonly class Response implements ResponseInterface
{
    public function __construct(
        public string $lotId,
        public string $status,
    ) {}
}
