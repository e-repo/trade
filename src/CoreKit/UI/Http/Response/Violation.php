<?php

declare(strict_types=1);

namespace CoreKit\UI\Http\Response;

use CoreKit\UI\Http\Response\Violation\ViolationItem;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

final readonly class Violation
{
    public function __construct(
        public string $message,
        /** @var ViolationItem[] $errors */
        #[OA\Property(ref: new Model(type: ViolationItem::class))]
        public array $errors = [],
    ) {}
}
