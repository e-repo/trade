<?php

declare(strict_types=1);

namespace Trade\UI\Http\V1\Lot\Create;

use CoreKit\UI\Http\Request\RequestPayloadInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final class Request implements RequestPayloadInterface
{
    #[Assert\Uuid(message: 'Cargo type ID must be a valid UUID')]
    #[Assert\NotBlank(message: 'Cargo type ID is required')]
    #[OA\Property(example: '550e8400-e29b-41d4-a716-446655440001')]
    public string $cargoTypeId;

    #[Assert\Positive(message: 'Total volume must be positive')]
    #[Assert\NotBlank(message: 'Total volume is required')]
    #[OA\Property(example: 1000)]
    public int $totalVolume;

    #[Assert\Positive(message: 'Start price must be positive')]
    #[Assert\NotBlank(message: 'Start price is required')]
    #[OA\Property(example: 50000, description: 'Price in kopecks')]
    public int $startPrice;

    #[Assert\Positive(message: 'Price step must be positive')]
    #[Assert\NotBlank(message: 'Price step is required')]
    #[OA\Property(example: 1000, description: 'Price step in kopecks')]
    public int $priceStep;

    #[Assert\Uuid(message: 'Volume step ID must be a valid UUID')]
    #[Assert\NotBlank(message: 'Volume step ID is required')]
    #[OA\Property(example: '550e8400-e29b-41d4-a716-446655440010')]
    public string $volumeStepId;

    #[Assert\Positive(message: 'Opens at must be a valid unix timestamp')]
    #[Assert\NotBlank(message: 'Opens at is required')]
    #[OA\Property(example: 1735689600, description: 'Unix timestamp when lot opens')]
    public int $opensAt;

    #[Assert\Positive(message: 'Closes at must be a valid unix timestamp')]
    #[Assert\NotBlank(message: 'Closes at is required')]
    #[OA\Property(example: 1735776000, description: 'Unix timestamp when lot closes')]
    public int $closesAt;
}
