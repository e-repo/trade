<?php

declare(strict_types=1);

namespace Test\Integration\Common\Fixture;

trait BaseFixtureTrait
{
    public static function getReferenceName(string|int $key, string $prefix = null): string
    {
        return sprintf('%s_%s', $prefix ?? self::getPrefix(), $key);
    }

    public static function allItems(): array
    {
        return [];
    }
}
