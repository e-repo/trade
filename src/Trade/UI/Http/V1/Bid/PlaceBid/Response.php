<?php

declare(strict_types=1);

namespace Trade\UI\Http\V1\Bid\PlaceBid;

use CoreKit\UI\Http\Response\ResponseInterface;
use OpenApi\Attributes as OA;

final readonly class Response implements ResponseInterface
{
    public function __construct(
        #[OA\Property(type: 'string', format: 'uuid')]
        public string $bidId,

        #[OA\Property(type: 'string', example: 'ACTIVE')]
        public string $status,

        #[OA\Property(type: 'integer', example: 50, description: 'Allocated volume in tons')]
        public int $allocatedVolume,

        #[OA\Property(type: 'integer', example: 50, description: 'Requested volume in tons')]
        public int $requestedVolume,
    ) {}
}
