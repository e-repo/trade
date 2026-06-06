<?php

declare(strict_types=1);

namespace Trade\UI\Http\V1\Lot\Create;

use CoreKit\UI\Http\Response\ResponseInterface;
use OpenApi\Attributes as OA;

final readonly class Response implements ResponseInterface
{
    public function __construct(
        #[OA\Property(description: 'Идентификатор созданного лота', type: 'string', format: 'uuid')]
        public string $lotId,

        #[OA\Property(description: 'Статус лота', type: 'string', example: 'CREATED')]
        public string $status,
    ) {}
}
