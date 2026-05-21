<?php

declare(strict_types=1);

namespace Trade\Domain\Dictionary\Entity;

use CoreKit\Domain\Entity\Id;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Trade\Infra\Dictionary\Repository\VolumeStepRepository;

#[ORM\Entity(repositoryClass: VolumeStepRepository::class)]
#[ORM\Table(schema: 'trade')]
class VolumeStep
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Id $id;

    #[ORM\Column(length: 50, unique: true)]
    private string $name;

    #[ORM\Column]
    private int $value;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    public function __construct(
        Id $id,
        string $name,
        int $value,
        DateTimeImmutable $createdAt,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->value = $value;
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

    public function getValue(): int
    {
        return $this->value;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
