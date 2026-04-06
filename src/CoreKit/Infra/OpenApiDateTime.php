<?php

declare(strict_types=1);

namespace CoreKit\Infra;

use DateTimeImmutable;
use JsonSerializable;

class OpenApiDateTime extends DateTimeImmutable implements JsonSerializable
{
    public function __construct(
        public string $datetime = "now",
        public string $format = DATE_ATOM,
    ) {
        parent::__construct($datetime);
    }

    public function jsonSerialize(): string
    {
        return $this
            ->format($this->format);
    }
}
