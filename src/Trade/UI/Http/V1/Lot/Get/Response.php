<?php

declare(strict_types=1);

namespace Trade\UI\Http\V1\Lot\Get;

use CoreKit\UI\Http\Response\ResponseInterface;
use OpenApi\Attributes as OA;

final readonly class Response implements ResponseInterface
{
    /**
     * @param string $lotId
     * @param string $status
     * @param int $totalVolume
     * @param int $startPrice
     * @param int $priceStep
     * @param int $opensAt Unix timestamp UTC
     * @param int $closesAt Unix timestamp UTC
     * @param string|null $closeReason
     * @param array<string> $winnerContractorIds
     */
    public function __construct(
        #[OA\Property(example: '550e8400-e29b-41d4-a716-446655440099')]
        public string $lotId,

        #[OA\Property(example: 'OPEN')]
        public string $status,

        #[OA\Property(example: 1000)]
        public int $totalVolume,

        #[OA\Property(example: 50000)]
        public int $startPrice,

        #[OA\Property(example: 1000)]
        public int $priceStep,

        #[OA\Property(example: 1735689600, description: 'Unix timestamp UTC')]
        public int $opensAt,

        #[OA\Property(example: 1735776000, description: 'Unix timestamp UTC')]
        public int $closesAt,

        #[OA\Property(example: 'EXPIRED')]
        public ?string $closeReason,

        #[OA\Property(
            type: 'array',
            items: new OA\Items(type: 'string', example: '550e8400-e29b-41d4-a716-446655440020'),
            description: 'ID контрагентов-победителей (пустой массив если лот не закрыт)'
        )]
        public array $winnerContractorIds,
    ) {}
}
