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
use Trade\Domain\Dictionary\Entity\Contractor;
use Trade\Domain\Lot\Entity\Bid;
use Trade\Domain\Lot\Entity\Lot;
use Trade\Domain\Lot\Enum\BidStatusEnum;

class BaseBidFixture extends Fixture implements ReferencableInterface, DependentFixtureInterface
{
    use BaseFixtureTrait;

    public function load(ObjectManager $manager): void
    {
        foreach (static::allItems() as $key => $item) {
            ++$key;

            $lot = $manager->find(Lot::class, new Id($item['lotId']));
            $contractor = $manager->find(Contractor::class, new Id($item['contractorId']));

            $bid = Bid::createPending(
                lot: $lot,
                contractor: $contractor,
                requestedVolume: $item['requestedVolume'],
                pricePerTon: $item['pricePerTon'],
            );

            if (isset($item['id'])) {
                $this->setBidId($bid, new Id($item['id']));
            }

            if (isset($item['allocatedVolume'])) {
                $this->setBidAllocatedVolume($bid, $item['allocatedVolume']);
            }

            if (isset($item['status'])) {
                $this->setBidStatus($bid, BidStatusEnum::from($item['status']));
            }

            if (isset($item['createdAt'])) {
                $this->setBidCreatedAt($bid, $item['createdAt']);
            }

            $manager->persist($bid);

            $this->addReference(self::getReferenceName($key), $bid);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            BaseLotFixture::class,
            BaseContractorFixture::class,
        ];
    }

    public static function getPrefix(): string
    {
        return 'trade-bid';
    }

    private function setBidId(Bid $bid, Id $id): void
    {
        $reflection = new ReflectionClass($bid);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($bid, $id);
    }

    private function setBidAllocatedVolume(Bid $bid, int $allocatedVolume): void
    {
        $reflection = new ReflectionClass($bid);
        $property = $reflection->getProperty('allocatedVolume');
        $property->setAccessible(true);
        $property->setValue($bid, $allocatedVolume);
    }

    private function setBidStatus(Bid $bid, BidStatusEnum $status): void
    {
        $reflection = new ReflectionClass($bid);
        $property = $reflection->getProperty('status');
        $property->setAccessible(true);
        $property->setValue($bid, $status);
    }

    private function setBidCreatedAt(Bid $bid, DateTimeImmutable $createdAt): void
    {
        $reflection = new ReflectionClass($bid);
        $property = $reflection->getProperty('createdAt');
        $property->setAccessible(true);
        $property->setValue($bid, $createdAt);
    }
}
