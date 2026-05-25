<?php

declare(strict_types=1);

namespace Test\Integration\Common\Fixture\Trade;

use CoreKit\Domain\Entity\Id;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Test\Integration\Common\Fixture\BaseFixtureTrait;
use Test\Integration\Common\Fixture\ReferencableInterface;
use Trade\Domain\Dictionary\Entity\Contractor;

class BaseContractorFixture extends Fixture implements ReferencableInterface
{
    use BaseFixtureTrait;

    public function load(ObjectManager $manager): void
    {
        foreach (static::allItems() as $key => $item) {
            ++$key;

            $contractor = new Contractor(
                id: new Id($item['id']),
                email: $item['email'],
                firstName: $item['firstName'],
                secondName: $item['secondName'],
                patronymic: $item['patronymic'] ?? null,
                agreementId: new Id($item['agreementId']),
                createdAt: new DateTimeImmutable(),
            );

            $manager->persist($contractor);

            $this->addReference(self::getReferenceName($key), $contractor);
        }

        $manager->flush();
    }

    public static function getPrefix(): string
    {
        return 'trade-contractor';
    }
}
