<?php

declare(strict_types=1);

namespace Trade\UI\Http\V1\Bid\PlaceBid;

use CoreKit\Application\Bus\CommandBusInterface;
use CoreKit\UI\Http\Response\ResponseWrapper;
use CoreKit\UI\Http\Response\Violation;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Trade\Application\Bid\Command\PlaceBid\Command;

#[OA\Tag(name: 'Trade - Ставки')]
#[OA\Post(
    summary: 'Разместить ставку на лот',
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(ref: new Model(type: Request::class))
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Ставка успешно размещена',
            content: new OA\JsonContent(
                ref: new Model(type: ResponseWrapper::class),
                example: new ResponseWrapper(
                    data: new Response(
                        bidId: '550e8400-e29b-41d4-a716-446655440002',
                        status: 'ACTIVE',
                        allocatedVolume: 50,
                        requestedVolume: 50
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
            response: 404,
            description: 'Лот или контрагент не найден',
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

    #[Route(path: '/api/v1/trade/bid', name: 'trade_place-bid', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] Request $request,
        HttpRequest $httpRequest
    ): JsonResponse {
        $contractorId = $httpRequest->headers->get('x-user-id');

        if ($contractorId === null) {
            throw new \RuntimeException('x-user-id header is required');
        }

        $result = $this->commandBus->dispatch(
            new Command(
                lotId: $request->lotId,
                contractorId: $contractorId,
                requestedVolume: $request->requestedVolume,
                pricePerTon: $request->pricePerTon,
            )
        );

        return new JsonResponse(
            new ResponseWrapper(
                data: new Response(
                    bidId: $result->bidId,
                    status: $result->status,
                    allocatedVolume: $result->allocatedVolume,
                    requestedVolume: $result->requestedVolume,
                )
            )
        );
    }
}
