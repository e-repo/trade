<?php

declare(strict_types=1);

namespace Test\Integration\Common\Fixture\Trade;

use CoreKit\Domain\Entity\Id;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Test\Integration\Common\Fixture\BaseFixtureTrait;
use Test\Integration\Common\Fixture\ReferencableInterface;
use Trade\Domain\Dictionary\Entity\CargoType;

class BaseCargoTypeFixture extends Fixture implements ReferencableInterface
{
    use BaseFixtureTrait;

    public function load(ObjectManager $manager): void
    {
        foreach (static::allItems() as $key => $item) {
            ++$key;

            $cargoType = new CargoType(
                id: new Id($item['id']),
                name: $item['name'],
                createdAt: new DateTimeImmutable(),
            );

            $manager->persist($cargoType);

            $this->addReference(self::getReferenceName($key), $cargoType);
        }

        $manager->flush();
    }

    public static function getPrefix(): string
    {
        return 'trade-cargo-type';
    }
}
