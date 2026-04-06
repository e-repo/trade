<?php

declare(strict_types=1);

namespace SomeModule\UI\Http\V1\Category\GetList;

use SomeModule\Application\Category\Query\GetList\Query;
use SomeModule\Application\Category\Query\GetList\Result;
use SomeModule\Domain\Post\Entity\Dto\CategoryDto;
use CoreKit\Application\Bus\QueryBusInterface;
use CoreKit\Infra\OpenApiDateTime;
use CoreKit\UI\Http\Response\ResponseMeta;
use CoreKit\UI\Http\Response\ResponseWrapper;
use CoreKit\UI\Http\Response\Violation;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[OA\Tag(name: 'Модуль: категория')]
#[OA\Get(
    summary: 'Получение списка категорий',
    parameters: [
        new OA\Parameter(ref: '#/components/parameters/limitParam'),
        new OA\Parameter(ref: '#/components/parameters/offsetParam'),
        new OA\Parameter(
            name: 'name',
            description: 'Имя категории',
            in: 'query',
            schema: new OA\Schema(type: 'string', example: 'Регуляторы роста')
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Список категорий',
            content: new OA\JsonContent(
                ref: new Model(type: ResponseWrapper::class),
                example: new ResponseWrapper(
                    data: [
                        new Response(
                            id: '789739fd-6c6f-4cb7-8eb8-3a548a1239b3',
                            name: 'Регуляторы роста',
                            description: 'Категория регуляторы роста содержит статьи на тему...',
                            createdAt: new OpenApiDateTime()
                        ),
                        new Response(
                            id: '1870317f-c545-43dc-907d-997cc0164f0d',
                            name: 'Ингибиторы роста',
                            description: 'Категория ингибиторы роста содержит статьи на тему...',
                            createdAt: new OpenApiDateTime()
                        ),
                    ]
                )
            )
        ),
        new OA\Response(
            response: 400,
            description: 'Некорректные данные запроса.',
            content: new Model(type: Violation::class),
        ),
        new OA\Response(
            response: 422,
            description: 'Ошибка бизнес-логики.',
            content: new Model(type: Violation::class),
        ),
    ]
)]
final class Action extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    #[Route(
        path: '/api/v1/module/categories',
        name: 'module_get-categories',
        methods: ['GET']
    )]
    public function __invoke(Request $request): ResponseWrapper
    {
        /** @var Result $result */
        $result = $this->queryBus->dispatch(
            new Query(
                name: $request->name,
                offset: $request->offset,
                limit: $request->limit,
            )
        );

        return new ResponseWrapper(
            data: array_map($this->makeCategoryResponse(...), $result->categories),
            meta: new ResponseMeta(
                offset: $request->offset,
                limit: $request->limit,
                total: $result->totalCount,
            )
        );
    }

    private function makeCategoryResponse(CategoryDto $category): Response
    {
        return new Response(
            id: $category->id,
            name: $category->name,
            description: $category->description,
            createdAt: $category->createdAt,
        );
    }
}
