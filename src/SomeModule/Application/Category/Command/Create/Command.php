<?php

declare(strict_types=1);

namespace SomeModule\Application\Category\Command\Create;

final readonly class Command
{
    public function __construct(
        public string $name,
        public string $description,
    ) {}
}
