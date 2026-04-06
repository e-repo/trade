<?php

declare(strict_types=1);

namespace SomeModule\UI\Http\V1\Category\Create;

use CoreKit\UI\Http\Response\ResponseInterface;

final class Response implements ResponseInterface
{
    public function __construct(
        public string $status,
    ) {}
}
