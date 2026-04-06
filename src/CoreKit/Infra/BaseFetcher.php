<?php

declare(strict_types=1);

namespace CoreKit\Infra;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

readonly class BaseFetcher
{
    public function __construct(
        private Connection $connection,
    ) {}

    protected function createDBALQueryBuilder(): QueryBuilder
    {
        return $this->connection->createQueryBuilder();
    }
}
