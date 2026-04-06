<?php

declare(strict_types=1);

namespace CoreKit\Application\Service\Thumbnail;

use SplFileInfo;

interface ThumbnailCreatorInterface
{
    public function setFile(SplFileInfo $file): self;

    public function setOption(Option $option): self;

    public function init(): self;

    public function resize(?int $width = null, ?int $height = null): self;

    public function scale(?int $width = null, ?int $height = null): self;

    public function save(?string $path = null): void;
}
