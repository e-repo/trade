<?php

declare(strict_types=1);

namespace Test\Integration\Trade\Console\CalculateWinners;

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
            [
                'id' => '550e8400-e29b-41d4-a716-446655440021',
                'email' => 'contractor2@example.com',
                'firstName' => 'Петр',
                'secondName' => 'Петров',
                'patronymic' => 'Петрович',
                'agreementId' => '550e8400-e29b-41d4-a716-446655440101',
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440022',
                'email' => 'contractor3@example.com',
                'firstName' => 'Сергей',
                'secondName' => 'Сергеев',
                'patronymic' => 'Сергеевич',
                'agreementId' => '550e8400-e29b-41d4-a716-446655440102',
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440023',
                'email' => 'contractor4@example.com',
                'firstName' => 'Алексей',
                'secondName' => 'Алексеев',
                'patronymic' => 'Алексеевич',
                'agreementId' => '550e8400-e29b-41d4-a716-446655440103',
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440024',
                'email' => 'contractor5@example.com',
                'firstName' => 'Дмитрий',
                'secondName' => 'Дмитриев',
                'patronymic' => 'Дмитриевич',
                'agreementId' => '550e8400-e29b-41d4-a716-446655440104',
            ],
            [
                'id' => '550e8400-e29b-41d4-a716-446655440025',
                'email' => 'contractor6@example.com',
                'firstName' => 'Михаил',
                'secondName' => 'Михайлов',
                'patronymic' => 'Михайлович',
                'agreementId' => '550e8400-e29b-41d4-a716-446655440105',
            ],
        ];
    }
}
