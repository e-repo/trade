<?php

declare(strict_types=1);

namespace Test\Common;

use JsonException;
use Symfony\Component\HttpFoundation\Response;

trait DataFromJsonResponseTrait
{
    /**
     * @throws JsonException
     */
    public function getDataFromJsonResponse(Response $response): array
    {
        return json_decode(
            $response->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}
