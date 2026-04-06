<?php

declare(strict_types=1);

namespace SomeModule\Application\Category\Query\GetList;

use SomeModule\Domain\Post\Fetcher\CategoryFetcherInterface;
use CoreKit\Application\Bus\QueryHandlerInterface;

final readonly class Handler implements QueryHandlerInterface
{
    public function __construct(
        private CategoryFetcherInterface $categoryFetcher,
    ) {}

    public function __invoke(Query $query): Result
    {
        $categories = $this->categoryFetcher
            ->findAllByName(
                name: $query->name,
                offset: $query->offset,
                limit: $query->limit,
            );

        return new Result(
            categories: $categories,
            totalCount: $this->categoryFetcher
                ->countByName(
                    $query->name
                ),
        );
    }
}
