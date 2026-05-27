<?php

declare(strict_types=1);

namespace Trade\UI\Http\V1\Bid\PlaceBid;

use CoreKit\UI\Http\Request\RequestPayloadInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'PlaceBidRequest',
    required: ['lotId', 'requestedVolume', 'pricePerTon'],
    properties: [
        new OA\Property(
            property: 'lotId',
            type: 'string',
            format: 'uuid',
            example: '550e8400-e29b-41d4-a716-446655440001'
        ),
        new OA\Property(
            property: 'requestedVolume',
            type: 'integer',
            example: 50
        ),
        new OA\Property(
            property: 'pricePerTon',
            type: 'integer',
            example: 150000
        ),
    ]
)]
final readonly class Request implements RequestPayloadInterface
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $lotId,

        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $requestedVolume,

        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $pricePerTon,
    ) {}
}
