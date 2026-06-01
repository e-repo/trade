<?php

declare(strict_types=1);

namespace Trade\UI\Http\V1\Bid\PlaceBid;

use CoreKit\Application\Bus\CommandBusInterface;
use CoreKit\UI\Http\Response\ResponseWrapper;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request as HttpRequest;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Trade\Application\Bid\Command\PlaceBid\Command;

#[OA\Post(
    path: '/api/v1/trade/bid',
    summary: 'Place a bid on a lot',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['lotId', 'requestedVolume', 'pricePerTon'],
            properties: [
                new OA\Property(property: 'lotId', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440001'),
                new OA\Property(property: 'requestedVolume', type: 'integer', example: 50, description: 'Requested volume in tons'),
                new OA\Property(property: 'pricePerTon', type: 'integer', example: 150000, description: 'Price per ton in kopecks'),
            ]
        )
    ),
    tags: ['Trade - Bids'],
    parameters: [
        new OA\Parameter(
            name: 'x-user-id',
            in: 'header',
            required: true,
            schema: new OA\Schema(type: 'string', format: 'uuid')
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Bid placed successfully',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'data',
                        properties: [
                            new OA\Property(property: 'bidId', type: 'string', format: 'uuid'),
                            new OA\Property(property: 'status', type: 'string', example: 'ACTIVE'),
                            new OA\Property(property: 'allocatedVolume', type: 'integer', example: 50, description: 'Allocated volume in tons'),
                            new OA\Property(property: 'requestedVolume', type: 'integer', example: 50, description: 'Requested volume in tons'),
                        ],
                        type: 'object'
                    ),
                ]
            )
        ),
        new OA\Response(response: 400, description: 'Validation error'),
        new OA\Response(response: 404, description: 'Lot or Contractor not found'),
        new OA\Response(response: 422, description: 'Business rule violation'),
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
