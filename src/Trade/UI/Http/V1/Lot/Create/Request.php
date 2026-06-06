<?php

declare(strict_types=1);

namespace Trade\UI\Http\V1\Lot\Create;

use CoreKit\UI\Http\Request\RequestPayloadInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final class Request implements RequestPayloadInterface
{
    #[Assert\Uuid(message: 'Идентификатор типа груза должен быть валидным UUID')]
    #[Assert\NotBlank(message: 'Идентификатор типа груза обязателен')]
    #[OA\Property(description: 'Идентификатор типа груза', example: '550e8400-e29b-41d4-a716-446655440001')]
    public string $cargoTypeId;

    #[Assert\Positive(message: 'Общий объем должен быть положительным числом')]
    #[Assert\NotBlank(message: 'Общий объем обязателен')]
    #[OA\Property(description: 'Общий объем в тоннах', example: 1000)]
    public int $totalVolume;

    #[Assert\Positive(message: 'Стартовая цена должна быть положительным числом')]
    #[Assert\NotBlank(message: 'Стартовая цена обязательна')]
    #[OA\Property(description: 'Стартовая цена за тонну в копейках', example: 50000)]
    public int $startPrice;

    #[Assert\Positive(message: 'Шаг цены должен быть положительным числом')]
    #[Assert\NotBlank(message: 'Шаг цены обязателен')]
    #[OA\Property(description: 'Шаг изменения цены в копейках', example: 1000)]
    public int $priceStep;

    #[Assert\Uuid(message: 'Идентификатор шага объема должен быть валидным UUID')]
    #[Assert\NotBlank(message: 'Идентификатор шага объема обязателен')]
    #[OA\Property(description: 'Идентификатор шага объема (грузоподъемность)', example: '550e8400-e29b-41d4-a716-446655440010')]
    public string $volumeStepId;

    #[Assert\Positive(message: 'Время открытия должно быть валидным unix timestamp')]
    #[Assert\NotBlank(message: 'Время открытия обязательно')]
    #[OA\Property(description: 'Unix timestamp времени открытия лота', example: 1735689600)]
    public int $opensAt;

    #[Assert\Positive(message: 'Время закрытия должно быть валидным unix timestamp')]
    #[Assert\NotBlank(message: 'Время закрытия обязательно')]
    #[OA\Property(description: 'Unix timestamp времени закрытия лота', example: 1735776000)]
    public int $closesAt;
}
