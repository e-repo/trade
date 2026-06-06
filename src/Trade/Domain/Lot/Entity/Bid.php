<?php

declare(strict_types=1);

namespace Trade\Domain\Lot\Entity;

use Carbon\Carbon;
use CoreKit\Domain\Entity\Id;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Trade\Domain\Dictionary\Entity\Contractor;
use Trade\Domain\Lot\Enum\BidStatusEnum;
use Trade\Infra\Lot\Repository\BidRepository;

#[ORM\Entity(repositoryClass: BidRepository::class)]
#[ORM\Table(schema: 'trade')]
#[ORM\Index(name: 'idx_bid_lot_id', columns: ['lot_id'])]
#[ORM\Index(name: 'idx_bid_contractor_id', columns: ['contractor_id'])]
#[ORM\Index(name: 'idx_bid_lot_price_allocated', columns: ['lot_id', 'price_per_ton', 'allocated_volume'])]
class Bid
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Id $id;

    #[ORM\ManyToOne(targetEntity: Lot::class, inversedBy: 'bids')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Lot $lot;

    #[ORM\ManyToOne(targetEntity: Contractor::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Contractor $contractor;

    #[ORM\Column(type: 'integer')]
    private int $requestedVolume;

    #[ORM\Column(type: 'integer')]
    private int $allocatedVolume = 0;

    #[ORM\Column(type: 'integer')]
    private int $pricePerTon;

    #[ORM\Column(type: 'string', enumType: BidStatusEnum::class)]
    private BidStatusEnum $status;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $rejectionReason = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    /**
     * Фабричный метод: создать ставку в ожидании (перед размещением)
     */
    public static function createPending(
        Lot $lot,
        Contractor $contractor,
        int $requestedVolume,
        int $pricePerTon,
    ): self {
        $bid = new self();
        $bid->id = Id::next();
        $bid->lot = $lot;
        $bid->contractor = $contractor;
        $bid->requestedVolume = $requestedVolume;
        $bid->pricePerTon = $pricePerTon;
        $bid->status = BidStatusEnum::PENDING;
        $bid->allocatedVolume = 0;
        $bid->createdAt = Carbon::now()->toDateTimeImmutable();

        return $bid;
    }

    /**
     * Выделить объём ставке
     */
    public function allocate(int $volume): void
    {
        $this->allocatedVolume = $volume;

        if ($volume === $this->requestedVolume) {
            $this->status = BidStatusEnum::ACTIVE;
            $this->updatedAt = Carbon::now()->toDateTimeImmutable();

            return;
        }

        if ($volume > 0) {
            $this->status = BidStatusEnum::PARTIALLY_ACTIVE;
        }

        $this->updatedAt = Carbon::now()->toDateTimeImmutable();
    }

    /**
     * Вытеснить ставку (полностью или частично)
     *
     * @return int — объём который был вытеснен
     */
    public function displace(int $volume): int
    {
        if ($volume >= $this->allocatedVolume) {
            // Полное вытеснение
            $displaced = $this->allocatedVolume;
            $this->allocatedVolume = 0;
            $this->status = BidStatusEnum::OUTBID;
            $this->updatedAt = Carbon::now()->toDateTimeImmutable();
            return $displaced;
        }

        // Частичное вытеснение
        $this->allocatedVolume -= $volume;
        $this->status = BidStatusEnum::PARTIALLY_ACTIVE;
        $this->updatedAt = Carbon::now()->toDateTimeImmutable();
        return $volume;
    }

    /**
     * Отклонить ставку
     */
    public function reject(string $reason): void
    {
        $this->allocatedVolume = 0;
        $this->status = BidStatusEnum::REJECTED;
        $this->rejectionReason = $reason;
        $this->updatedAt = Carbon::now()->toDateTimeImmutable();
    }

    // Getters

    public function getId(): Id
    {
        return $this->id;
    }

    public function getLot(): Lot
    {
        return $this->lot;
    }

    public function getContractor(): Contractor
    {
        return $this->contractor;
    }

    public function getRequestedVolume(): int
    {
        return $this->requestedVolume;
    }

    public function getAllocatedVolume(): int
    {
        return $this->allocatedVolume;
    }

    public function getPricePerTon(): int
    {
        return $this->pricePerTon;
    }

    public function getStatus(): BidStatusEnum
    {
        return $this->status;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function hasAllocatedVolume(): bool
    {
        return $this->allocatedVolume > 0;
    }

    public function isAccepted(): bool
    {
        return $this->status === BidStatusEnum::ACTIVE
            || $this->status === BidStatusEnum::PARTIALLY_ACTIVE;
    }
}
