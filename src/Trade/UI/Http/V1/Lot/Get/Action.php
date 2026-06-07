<?php

declare(strict_types=1);

namespace Trade\UI\Http\V1\Lot\Get;

use CoreKit\Application\Bus\QueryBusInterface;
use CoreKit\Domain\Entity\Id;
use CoreKit\UI\Http\Response\ResponseWrapper;
use CoreKit\UI\Http\Response\Violation;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Trade\Application\Lot\Query\Get\Query;

#[OA\Tag(name: 'Trade - Лоты')]
#[OA\Get(
    summary: 'Получить информацию о лоте',
    parameters: [
        new OA\Parameter(
            name: 'lotId',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string', format: 'uuid'),
            example: '550e8400-e29b-41d4-a716-446655440099'
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Информация о лоте',
            content: new OA\JsonContent(
                ref: new Model(type: ResponseWrapper::class),
                example: new ResponseWrapper(
                    data: new Response(
                        lotId: '550e8400-e29b-41d4-a716-446655440099',
                        status: 'CLOSED',
                        totalVolume: 1000,
                        startPrice: 50000,
                        priceStep: 1000,
                        opensAt: 1735689600,
                        closesAt: 1735776000,
                        closeReason: 'EXPIRED',
                        winnerContractorIds: [
                            '550e8400-e29b-41d4-a716-446655440020',
                            '550e8400-e29b-41d4-a716-446655440021',
                        ]
                    )
                )
            )
        ),
        new OA\Response(
            response: 404,
            description: 'Лот не найден',
            content: new Model(type: Violation::class)
        ),
    ]
)]
final class Action extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    #[Route(
        path: '/api/v1/trade/lot/{lotId}',
        name: 'trade_get-lot',
        methods: ['GET']
    )]
    public function __invoke(string $lotId): JsonResponse
    {
        if (!Uuid::isValid($lotId)) {
            throw new BadRequestHttpException('Invalid lot ID format');
        }

        $result = $this->queryBus->dispatch(
            new Query(lotId: new Id($lotId))
        );

        return new JsonResponse(
            new ResponseWrapper(
                data: new Response(
                    lotId: $result->lotId,
                    status: $result->status,
                    totalVolume: $result->totalVolume,
                    startPrice: $result->startPrice,
                    priceStep: $result->priceStep,
                    opensAt: $result->opensAt->getTimestamp(),
                    closesAt: $result->closesAt->getTimestamp(),
                    closeReason: $result->closeReason,
                    winnerContractorIds: $result->winnerContractorIds,
                )
            )
        );
    }
}
