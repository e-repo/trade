<?php

declare(strict_types=1);

namespace Trade\Domain\Trade\Entity;

use CoreKit\Domain\Entity\Id;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Trade\Infra\Trade\Repository\ContractorRepository;

#[ORM\Entity(repositoryClass: ContractorRepository::class)]
#[ORM\Table(schema: 'trade')]
class Contractor
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Id $id;

    #[ORM\Column(length: 255, unique: true)]
    private string $email;

    #[ORM\Column(length: 100)]
    private string $firstName;

    #[ORM\Column(length: 100)]
    private string $secondName;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $patronymic;

    #[ORM\Column(type: 'uuid')]
    private Id $agreementId;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct(
        Id $id,
        string $email,
        string $firstName,
        string $secondName,
        ?string $patronymic,
        Id $agreementId,
        DateTimeImmutable $createdAt,
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->firstName = $firstName;
        $this->secondName = $secondName;
        $this->patronymic = $patronymic;
        $this->agreementId = $agreementId;
        $this->createdAt = $createdAt;
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getSecondName(): string
    {
        return $this->secondName;
    }

    public function getPatronymic(): ?string
    {
        return $this->patronymic;
    }

    public function getAgreementId(): Id
    {
        return $this->agreementId;
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
