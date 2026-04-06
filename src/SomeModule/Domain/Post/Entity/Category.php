<?php

declare(strict_types=1);

namespace SomeModule\Domain\Post\Entity;

use SomeModule\Domain\Post\Entity\Dto\CategoryDto;
use SomeModule\Domain\Post\Entity\Specification\Category\SpecificationAggregator;
use SomeModule\Infra\Post\Repository\CategoryRepository;
use CoreKit\Domain\Entity\Id;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use DomainException;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\Table(schema: 'module')]
class Category
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', options: [
        'comment' => 'Код категории',
    ])]
    private Id $id;

    #[ORM\Column(length: 50, unique: true, options: [
        'comment' => 'Наименование категории',
    ])]
    private string $name;

    #[ORM\Column(length: 255, options: [
        'comment' => 'Описание категории',
    ])]
    private string $description;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, options: [
        'comment' => 'Дата создания категории',
    ])]
    private DateTimeImmutable $createdAt;

    public function __construct(
        CategoryDto $categoryDto,
        SpecificationAggregator $specificationAggregator,
    ) {
        $this->id = null === $categoryDto->id
            ? Id::next()
            : new Id($categoryDto->id);

        $this->name = $categoryDto->name;
        $this->description = $categoryDto->description;
        $this->createdAt = new DateTimeImmutable();

        $this->checkSpecifications($specificationAggregator);
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    private function checkSpecifications(SpecificationAggregator $aggregator): void
    {
        if (! $aggregator->uniqueNameSpecification->isSatisfiedBy($this)) {
            throw new DomainException('Категория поста с данными наименованием уже существует.');
        }
    }
}
