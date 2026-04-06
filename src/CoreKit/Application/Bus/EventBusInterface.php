<?php

declare(strict_types=1);

namespace CoreKit\Application\Bus;

interface EventBusInterface
{
    public function publish(object $event): void;
}
