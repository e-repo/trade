<?php

declare(strict_types=1);

namespace Trade\UI\Http\V1\Lot\Create;

use Carbon\Carbon;
use CoreKit\Application\Bus\CommandBusInterface;
use CoreKit\UI\Http\Response\ResponseWrapper;
use CoreKit\UI\Http\Response\Violation;
use DateTimeImmutable;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Trade\Application\Lot\Command\Create\Command;

#[OA\Tag(name: 'Trade - Лоты')]
#[OA\Post(
    summary: 'Создать лот',
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(ref: new Model(type: Request::class))
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Лот успешно создан',
            content: new OA\JsonContent(
                ref: new Model(type: ResponseWrapper::class),
                example: new ResponseWrapper(
                    data: new Response(
                        lotId: '550e8400-e29b-41d4-a716-446655440099',
                        status: 'CREATED'
                    )
                )
            )
        ),
        new OA\Response(
            response: 400,
            description: 'Некорректные данные запроса',
            content: new Model(type: Violation::class)
        ),
        new OA\Response(
            response: 422,
            description: 'Нарушение бизнес-правил',
            content: new Model(type: Violation::class)
        ),
    ]
)]
final class Action extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    #[Route(
        path: '/api/v1/trade/lot',
        name: 'trade_create-lot',
        methods: ['POST']
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $result = $this->commandBus->dispatch(
            new Command(
                cargoTypeId: $request->cargoTypeId,
                totalVolume: $request->totalVolume,
                startPrice: $request->startPrice,
                priceStep: $request->priceStep,
                volumeStepId: $request->volumeStepId,
                opensAt: Carbon::createFromTimestamp($request->opensAt)->toDateTimeImmutable(),
                closesAt: Carbon::createFromTimestamp($request->closesAt)->toDateTimeImmutable(),
            )
        );

        return new JsonResponse(
            new ResponseWrapper(
                data: new Response(
                    lotId: $result->lotId,
                    status: $result->status,
                )
            )
        );
    }
}
