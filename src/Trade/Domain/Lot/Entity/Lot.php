<?php

declare(strict_types=1);

namespace Trade\Domain\Lot\Entity;

use CoreKit\Domain\Entity\Id;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use DomainException;
use Trade\Domain\Dictionary\Entity\CargoType;
use Trade\Domain\Dictionary\Entity\VolumeStep;
use Trade\Domain\Lot\Enum\LotStatusEnum;
use Trade\Domain\Lot\ValueObject\LotTermination;
use Trade\Domain\Lot\ValueObject\Price;
use Trade\Domain\Lot\ValueObject\Volume;
use Trade\Infra\Lot\Repository\LotRepository;

#[ORM\Entity(repositoryClass: LotRepository::class)]
#[ORM\Table(schema: 'trade')]
#[ORM\Index(name: 'idx_lot_status', columns: ['status'])]
#[ORM\Index(name: 'idx_lot_opens_at', columns: ['opens_at'])]
#[ORM\Index(name: 'idx_lot_closes_at', columns: ['termination_closes_at'])]
class Lot
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Id $id;

    #[ORM\ManyToOne(targetEntity: CargoType::class)]
    #[ORM\JoinColumn(nullable: false)]
    private CargoType $cargoType;

    #[ORM\Embedded(class: Volume::class)]
    private Volume $volume;

    #[ORM\Embedded(class: Price::class)]
    private Price $price;

    #[ORM\Column(enumType: LotStatusEnum::class)]
    private LotStatusEnum $status;

    #[ORM\Column]
    private DateTimeImmutable $opensAt;

    #[ORM\Embedded(class: LotTermination::class)]
    private LotTermination $termination;

    #[ORM\ManyToOne(targetEntity: VolumeStep::class)]
    #[ORM\JoinColumn(nullable: false)]
    private VolumeStep $volumeStep;

    #[ORM\Column]
    private int $version = 1;

    #[ORM\Column]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct(
        CargoType $cargoType,
        int $totalVolume,
        int $startPrice,
        int $priceStep,
        VolumeStep $volumeStep,
        DateTimeImmutable $opensAt,
        DateTimeImmutable $closesAt,
    ) {
        $this->id = Id::next();
        $this->cargoType = $cargoType;
        $this->volumeStep = $volumeStep;
        $this->volume = new Volume($totalVolume, $volumeStep->getValue());
        $this->price = new Price($startPrice, $priceStep);
        $this->opensAt = $opensAt;
        $this->termination = new LotTermination($closesAt);
        $this->status = LotStatusEnum::CREATED;
        $this->createdAt = new DateTimeImmutable();

        $this->validateDates($opensAt, $closesAt);
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getStatus(): LotStatusEnum
    {
        return $this->status;
    }

    public function getFreeVolume(): int
    {
        return $this->volume->getFreeVolume();
    }

    public function canAcceptBids(): bool
    {
        return $this->status === LotStatusEnum::OPEN
            && new DateTimeImmutable() <= $this->termination->getClosesAt();
    }

    private function validateDates(DateTimeImmutable $opensAt, DateTimeImmutable $closesAt): void
    {
        if ($opensAt >= $closesAt) {
            throw new DomainException('Opens at must be before closes at');
        }

        $now = new DateTimeImmutable();
        if ($closesAt <= $now) {
            throw new DomainException('Closes at must be in the future');
        }
    }
}
