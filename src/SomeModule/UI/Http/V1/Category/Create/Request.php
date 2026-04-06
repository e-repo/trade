<?php

declare(strict_types=1);

namespace SomeModule\UI\Http\V1\Category\Create;

use CoreKit\Infra\Validator\NotWhitespace\NotWhitespace;
use CoreKit\UI\Http\Request\RequestPayloadInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

final class Request implements RequestPayloadInterface
{
    #[Assert\Length(
        min: 2,
        max: 200,
        minMessage: 'Наименование категории не может содержать менее 2 символов.',
        maxMessage: 'Наименование категории не может содержать более 50 символов.'
    )]
    #[NotWhitespace]
    #[Assert\NotBlank(message: 'Наименование категории является обязательным полем для заполнения.')]
    #[OA\Property(example: 'Регуляторы роста')]
    public string $name;

    #[Assert\Length(
        min: 2,
        max: 200,
        minMessage: 'Описание категории не может содержать менее 2 символов.',
        maxMessage: 'Описание категории не может содержать более 250 символов.'
    )]
    #[NotWhitespace]
    #[Assert\NotBlank(message: 'Описание категории является обязательным полем для заполнения.')]
    #[OA\Property(example: 'Категория регуляторы роста содержит статьи на тему...')]
    public string $description;
}
