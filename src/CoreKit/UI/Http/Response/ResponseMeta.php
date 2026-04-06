<?php

declare(strict_types=1);

namespace CoreKit\UI\Http\Response;

final readonly class ResponseMeta
{
    public function __construct(
        public int $offset,
        public int $limit = 100,
        public int $total = 0,
    ) {}
}
