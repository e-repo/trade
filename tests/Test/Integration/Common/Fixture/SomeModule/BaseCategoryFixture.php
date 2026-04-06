<?php

declare(strict_types=1);

namespace Test\Integration\Common\Fixture\SomeModule;

use SomeModule\Domain\Post\Entity\Category;
use SomeModule\Domain\Post\Entity\Dto\CategoryDto;
use SomeModule\Domain\Post\Entity\Specification\Category\SpecificationAggregator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Test\Integration\Common\Fixture\BaseFixtureTrait;
use Test\Integration\Common\Fixture\ReferencableInterface;

class BaseCategoryFixture extends Fixture implements ReferencableInterface
{
    use BaseFixtureTrait;

    public function __construct(
        private readonly SpecificationAggregator $specificationAggregator
    ) {}

    public function load(ObjectManager $manager): void
    {
        foreach (static::allItems() as $key => $item) {
            ++$key;

            $category = new Category(
                categoryDto: new CategoryDto(
                    name: $item['name'],
                    description: $item['description'],
                    id: $item['id'],
                ),
                specificationAggregator: $this->specificationAggregator,
            );

            $manager->persist($category);

            $this->addReference(self::getReferenceName($key), $category);
        }

        $manager->flush();
    }

    public static function getPrefix(): string
    {
        return 'module-category';
    }
}
