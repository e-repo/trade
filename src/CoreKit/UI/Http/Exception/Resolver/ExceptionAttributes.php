<?php

declare(strict_types=1);

namespace CoreKit\UI\Http\Exception\Resolver;

final readonly class ExceptionAttributes
{
    public function __construct(
        public int $code,
        public bool $hidden,
        public bool $loggable,
    ) {}

    public static function fromExceptionCode(int $code, bool $isHidden = true): self
    {
        return new self(
            code: $code,
            hidden: $isHidden,
            loggable: false
        );
    }
}
