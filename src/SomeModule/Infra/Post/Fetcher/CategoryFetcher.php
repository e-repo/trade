<?php

declare(strict_types=1);

namespace SomeModule\Infra\Post\Fetcher;

use SomeModule\Domain\Post\Entity\Dto\CategoryDto;
use SomeModule\Domain\Post\Fetcher\CategoryFetcherInterface;
use Carbon\Carbon;
use CoreKit\Infra\BaseFetcher;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;

final readonly class CategoryFetcher extends BaseFetcher implements CategoryFetcherInterface
{
    private const TABLE_NAME = 'module.category';

    /**
     * @throws Exception
     */
    public function findById(string $id): ?CategoryDto
    {
        $qb = $this->createDBALQueryBuilder();

        $category = $qb
            ->select('*')
            ->from(self::TABLE_NAME, 'c')
            ->where(
                $qb->expr()->eq('c.id', ':categoryId')
            )
            ->setParameter('categoryId', $id)
            ->fetchAssociative();

        return $category ? $this->toCategoryDto($category) : null;
    }

    /**
     * @throws Exception
     */
    public function findAllByName(?string $name, int $offset, int $limit): array
    {
        $qb = $this->makeQBByName($name)
            ->select('*')
            ->orderBy('c.name')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return array_map($this->toCategoryDto(...), $qb->fetchAllAssociative());
    }

    /**
     * @throws Exception
     */
    public function countByName(?string $name): int
    {
        return (int) $this->makeQBByName($name)
            ->select('count(*)')
            ->fetchOne();
    }

    private function toCategoryDto(array $category): CategoryDto
    {
        return new CategoryDto(
            name: $category['name'],
            description: $category['description'],
            id: $category['id'],
            createdAt: Carbon::createFromFormat('Y-m-d H:i:sT', $category['created_at'])
                ?->toDateTimeImmutable(),
        );
    }

    private function makeQBByName(?string $name): QueryBuilder
    {
        $qb = $this->createDBALQueryBuilder()
            ->from(self::TABLE_NAME, 'c');

        if (null !== $name) {
            $qb
                ->where(
                    $qb->expr()->like('c.name', ':name')
                )
                ->setParameter('name', "$name%");
        }

        return $qb;
    }
}
