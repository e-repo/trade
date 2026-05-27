<?php

declare(strict_types=1);

namespace Test\Integration\Common\Fixture\Trade;

use CoreKit\Domain\Entity\Id;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use ReflectionClass;
use Test\Integration\Common\Fixture\BaseFixtureTrait;
use Test\Integration\Common\Fixture\ReferencableInterface;
use Trade\Domain\Dictionary\Entity\CargoType;
use Trade\Domain\Dictionary\Entity\VolumeStep;
use Trade\Domain\Lot\Entity\Lot;
use Trade\Domain\Lot\Enum\LotStatusEnum;

class BaseLotFixture extends Fixture implements ReferencableInterface, DependentFixtureInterface
{
    use BaseFixtureTrait;

    public function load(ObjectManager $manager): void
    {
        foreach (static::allItems() as $key => $item) {
            ++$key;

            $cargoType = $manager->find(CargoType::class, new Id($item['cargoTypeId']));
            $volumeStep = $manager->find(VolumeStep::class, new Id($item['volumeStepId']));

            $lot = new Lot(
                cargoType: $cargoType,
                totalVolume: $item['totalVolume'],
                startPrice: $item['startPrice'],
                priceStep: $item['priceStep'],
                volumeStep: $volumeStep,
                opensAt: $item['opensAt'],
                closesAt: $item['closesAt'],
            );

            if (isset($item['status']) && $item['status'] === LotStatusEnum::OPEN->value) {
                $this->setLotStatus($lot, LotStatusEnum::OPEN);
            }

            if (isset($item['id'])) {
                $this->setLotId($lot, new Id($item['id']));
            }

            $manager->persist($lot);

            $this->addReference(self::getReferenceName($key), $lot);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            BaseCargoTypeFixture::class,
            BaseVolumeStepFixture::class,
        ];
    }

    public static function getPrefix(): string
    {
        return 'trade-lot';
    }

    private function setLotStatus(Lot $lot, LotStatusEnum $status): void
    {
        $reflection = new ReflectionClass($lot);
        $property = $reflection->getProperty('status');
        $property->setAccessible(true);
        $property->setValue($lot, $status);
    }

    private function setLotId(Lot $lot, Id $id): void
    {
        $reflection = new ReflectionClass($lot);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($lot, $id);
    }
}
