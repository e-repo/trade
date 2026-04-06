<?php

declare(strict_types=1);

namespace CoreKit\Application\Service\Thumbnail;

final readonly class Option
{
    public function __construct(
        public bool $autoOrientation = true,
        public bool $decodeAnimation = true,
        public mixed $blendingColor = 'ffffff',
    ) {}
}
