<?php

declare(strict_types=1);

namespace CoreKit\Application;

interface FlusherInterface
{
    public function flush(): void;
}
