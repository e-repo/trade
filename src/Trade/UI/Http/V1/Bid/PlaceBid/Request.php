<?php

declare(strict_types=1);

namespace Trade\UI\Http\V1\Bid\PlaceBid;

use CoreKit\UI\Http\Request\RequestPayloadInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class Request implements RequestPayloadInterface
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        #[OA\Property(example: '550e8400-e29b-41d4-a716-446655440001')]
        public string $lotId,

        #[Assert\NotBlank]
        #[Assert\Positive]
        #[OA\Property(example: 50, description: 'Requested volume in tons')]
        public int $requestedVolume,

        #[Assert\NotBlank]
        #[Assert\Positive]
        #[OA\Property(example: 150000, description: 'Price per ton in kopecks')]
        public int $pricePerTon,
    ) {}
}
