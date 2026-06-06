<?php

declare(strict_types=1);

namespace Trade\UI\Http\V1\Bid\PlaceBid;

use CoreKit\UI\Http\Request\RequestPayloadInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class Request implements RequestPayloadInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Идентификатор лота обязателен')]
        #[Assert\Uuid(message: 'Идентификатор лота должен быть валидным UUID')]
        #[OA\Property(description: 'Идентификатор лота', example: '550e8400-e29b-41d4-a716-446655440001')]
        public string $lotId,

        #[Assert\NotBlank(message: 'Запрашиваемый объем обязателен')]
        #[Assert\Positive(message: 'Запрашиваемый объем должен быть положительным числом')]
        #[OA\Property(description: 'Запрашиваемый объем в тоннах', example: 50)]
        public int $requestedVolume,

        #[Assert\NotBlank(message: 'Цена за тонну обязательна')]
        #[Assert\Positive(message: 'Цена за тонну должна быть положительным числом')]
        #[OA\Property(description: 'Цена за тонну в копейках', example: 150000)]
        public int $pricePerTon,
    ) {}
}
