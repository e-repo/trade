<?php

declare(strict_types=1);

namespace Test\Integration\Trade\Api\CreateLot;

use Test\Integration\Common\Fixture\Trade\BaseContractorFixture;

final class ContractorFixture extends BaseContractorFixture
{
    public static function allItems(): array
    {
        return [
            [
                'id' => '550e8400-e29b-41d4-a716-446655440020',
                'email' => 'contractor1@example.com',
                'firstName' => 'Иван',
                'secondName' => 'Иванов',
                'patronymic' => 'Иванович',
                'agreementId' => '550e8400-e29b-41d4-a716-446655440100',
            ],
        ];
    }
}
