<?php

declare(strict_types=1);

namespace CoreKit\UI\Http\Response;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class ResponseFactory
{
    public function __construct(
        private SerializerInterface $serializer,
    ) {}

    public function toJsonResponse(
        mixed $data,
        int $status = Response::HTTP_OK,
        array $headers = [],
        array $context = []
    ): JsonResponse {
        $json = $this->serializer
            ->serialize(
                data: $data,
                format: JsonEncoder::FORMAT,
                context: array_merge(
                    [
                        'json_encode_options' =>
                            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE,
                    ],
                    $context
                )
            );

        return new JsonResponse($json, $status, $headers, true);
    }

    public function toBadRequestJsonResponse(
        mixed $data,
        array $headers = [],
        array $context = []
    ): JsonResponse {
        return $this->toJsonResponse($data, Response::HTTP_BAD_REQUEST, $headers, $context);
    }

    public function toUnprocessableEntityJsonResponse(
        mixed $data,
        array $headers = [],
        array $context = []
    ): JsonResponse {
        return $this->toJsonResponse($data, Response::HTTP_UNPROCESSABLE_ENTITY, $headers, $context);
    }

    public function toNotFoundJsonResponse(
        mixed $data,
        array $headers = [],
        array $context = []
    ): JsonResponse {
        return $this->toJsonResponse($data, Response::HTTP_NOT_FOUND, $headers, $context);
    }
}
