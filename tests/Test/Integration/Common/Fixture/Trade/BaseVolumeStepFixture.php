<?php

declare(strict_types=1);

namespace Test\Integration\Common\Fixture\Trade;

use CoreKit\Domain\Entity\Id;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Test\Integration\Common\Fixture\BaseFixtureTrait;
use Test\Integration\Common\Fixture\ReferencableInterface;
use Trade\Domain\Dictionary\Entity\VolumeStep;

class BaseVolumeStepFixture extends Fixture implements ReferencableInterface
{
    use BaseFixtureTrait;

    public function load(ObjectManager $manager): void
    {
        foreach (static::allItems() as $key => $item) {
            ++$key;

            $volumeStep = new VolumeStep(
                id: new Id($item['id']),
                name: $item['name'],
                value: $item['value'],
                createdAt: new DateTimeImmutable(),
            );

            $manager->persist($volumeStep);

            $this->addReference(self::getReferenceName($key), $volumeStep);
        }

        $manager->flush();
    }

    public static function getPrefix(): string
    {
        return 'trade-volume-step';
    }
}
