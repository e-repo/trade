<?php

declare(strict_types=1);

namespace CoreKit\Application\Service\Thumbnail;

use SplFileInfo;

interface ThumbnailCreatorFactoryInterface
{
    public function create(SplFileInfo $file, ?Option $option = null): ThumbnailCreatorInterface;
}
