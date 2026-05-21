<?php

declare(strict_types=1);

namespace Trade\Domain\Dictionary\Entity;

use CoreKit\Domain\Entity\Id;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Trade\Infra\Dictionary\Repository\CargoTypeRepository;

#[ORM\Entity(repositoryClass: CargoTypeRepository::class)]
#[ORM\Table(schema: 'trade')]
class CargoType
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Id $id;

    #[ORM\Column(length: 100, unique: true)]
    private string $name;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct(
        Id $id,
        string $name,
        DateTimeImmutable $createdAt,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->createdAt = $createdAt;
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
