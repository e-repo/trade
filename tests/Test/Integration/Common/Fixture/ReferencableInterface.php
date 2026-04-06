<?php

declare(strict_types=1);

namespace Test\Integration\Common\Fixture;

/**
 * Реализуется через - Test\Functional\Common\Fixture\BaseFixtureTrait
 */
interface ReferencableInterface
{
    public static function getPrefix(): string;

    public static function getReferenceName(string|int $key): string;
}
