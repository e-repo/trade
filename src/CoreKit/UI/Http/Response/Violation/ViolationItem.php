<?php

declare(strict_types=1);

namespace CoreKit\UI\Http\Response\Violation;

use OpenApi\Attributes as OA;

final readonly class ViolationItem
{
    public function __construct(
        public ?string $detail,
        public string $source,
        #[OA\Property(type: 'object')]
        public array $data = []
    ) {}
}
