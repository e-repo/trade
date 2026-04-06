<?php

declare(strict_types=1);

namespace SomeModule\UI\Http\V1\Category\GetList;

use CoreKit\Infra\Validator\NotWhitespace\NotWhitespace;
use CoreKit\UI\Http\Request\RequestPayloadInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final class Request implements RequestPayloadInterface
{
    #[Assert\NotNull(message: 'Не заполнено поле offset')]
    #[Assert\PositiveOrZero(message: 'offset должен быть положительным либо равным нулю')]
    public int $offset;

    #[Assert\NotNull(message: 'Не заполнено поле limit')]
    #[Assert\Positive(message: 'limit не должен быть отрицательным')]
    #[Assert\LessThanOrEqual(value: 100, message: 'limit не может превышать значение 100')]
    public int $limit;

    #[Assert\Length(
        min: 2,
        max: 200,
        minMessage: 'Наименование категории не может содержать менее 2 символов.',
        maxMessage: 'Наименование категории не может содержать более 50 символов.'
    )]
    #[NotWhitespace]
    #[OA\Property(example: 'Регуляторы роста')]
    public ?string $name = null;
}
