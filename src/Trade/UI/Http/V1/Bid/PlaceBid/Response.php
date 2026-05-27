<?php

declare(strict_types=1);

namespace Trade\UI\Http\V1\Bid\PlaceBid;

use CoreKit\UI\Http\Response\ResponseInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PlaceBidResponse',
    properties: [
        new OA\Property(property: 'bidId', type: 'string', format: 'uuid'),
        new OA\Property(property: 'status', type: 'string', example: 'ACTIVE'),
        new OA\Property(property: 'allocatedVolume', type: 'integer', example: 50),
        new OA\Property(property: 'requestedVolume', type: 'integer', example: 50),
    ]
)]
final readonly class Response implements ResponseInterface
{
    public function __construct(
        public string $bidId,
        public string $status,
        public int $allocatedVolume,
        public int $requestedVolume,
    ) {}
}
