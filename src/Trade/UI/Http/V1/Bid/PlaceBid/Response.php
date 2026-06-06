<?php

declare(strict_types=1);

namespace Trade\UI\Http\V1\Bid\PlaceBid;

use CoreKit\UI\Http\Response\ResponseInterface;
use OpenApi\Attributes as OA;

final readonly class Response implements ResponseInterface
{
    public function __construct(
        #[OA\Property(description: 'Идентификатор ставки', type: 'string', format: 'uuid')]
        public string $bidId,

        #[OA\Property(description: 'Статус ставки', type: 'string', example: 'ACTIVE')]
        public string $status,

        #[OA\Property(description: 'Выделенный объем в тоннах', type: 'integer', example: 50)]
        public int $allocatedVolume,

        #[OA\Property(description: 'Запрашиваемый объем в тоннах', type: 'integer', example: 50)]
        public int $requestedVolume,
    ) {}
}
