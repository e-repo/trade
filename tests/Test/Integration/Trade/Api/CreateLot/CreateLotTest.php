<?php

declare(strict_types=1);

namespace Test\Integration\Trade\Api\CreateLot;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Component\HttpFoundation\Response;
use Test\Common\DataFromJsonResponseTrait;
use Test\Common\FunctionalTestCase;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

final class CreateLotTest extends FunctionalTestCase
{
    use DataFromJsonResponseTrait;
    use InteractsWithMessenger;

    private const ENDPOINT_URL = '/api/v1/trade/lot';

    public function setUp(): void
    {
        parent::setUp();

        $this->databaseTool
            ->withPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE)
            ->loadFixtures([
                CargoTypeFixture::class,
                VolumeStepFixture::class,
                ContractorFixture::class,
            ]);
    }

    public function testSuccessCreateLot(): void
    {
        // arrange
        $cargoTypeId = '550e8400-e29b-41d4-a716-446655440001';
        $volumeStepId = '550e8400-e29b-41d4-a716-446655440010';
        $totalVolume = 1000;
        $startPrice = 50000;
        $priceStep = 1000;
        $opensAt = strtotime('+1 day');
        $closesAt = strtotime('+2 days');

        $client = $this->createClient();

        // act
        $client->jsonRequest(
            method: 'POST',
            uri: self::ENDPOINT_URL,
            parameters: [
                'cargoTypeId' => $cargoTypeId,
                'totalVolume' => $totalVolume,
                'startPrice' => $startPrice,
                'priceStep' => $priceStep,
                'volumeStepId' => $volumeStepId,
                'opensAt' => $opensAt,
                'closesAt' => $closesAt,
            ]
        );

        $response = $this->getDataFromJsonResponse($client->getResponse());

        // assert
        self::assertResponseIsSuccessful();
        self::assertArrayHasKey('data', $response);
        self::assertArrayHasKey('lotId', $response['data']);
        self::assertArrayHasKey('status', $response['data']);
        self::assertEquals('CREATED', $response['data']['status']);
        self::assertNotEmpty($response['data']['lotId']);

        // verify lot was created in database using DQL
        $query = $this->entityManager->createQuery('SELECT COUNT(l.id) FROM Trade\Domain\Lot\Entity\Lot l');
        $count = $query->getSingleScalarResult();
        self::assertEquals(1, $count);
    }

    public function testFailedByInvalidCargoTypeUuid(): void
    {
        // arrange
        $cargoTypeId = 'invalid-uuid';
        $volumeStepId = '550e8400-e29b-41d4-a716-446655440010';
        $totalVolume = 1000;
        $startPrice = 50000;
        $priceStep = 1000;
        $opensAt = strtotime('+1 day');
        $closesAt = strtotime('+2 days');

        $client = $this->createClient();

        // act
        $client->jsonRequest(
            method: 'POST',
            uri: self::ENDPOINT_URL,
            parameters: [
                'cargoTypeId' => $cargoTypeId,
                'totalVolume' => $totalVolume,
                'startPrice' => $startPrice,
                'priceStep' => $priceStep,
                'volumeStepId' => $volumeStepId,
                'opensAt' => $opensAt,
                'closesAt' => $closesAt,
            ]
        );

        $response = $this->getDataFromJsonResponse($client->getResponse());

        // assert
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertEquals('Некорректные данные запроса.', $response['message']);
        self::assertCount(1, $response['errors']);
        self::assertEquals('cargoTypeId', $response['errors'][0]['source']);
    }

    public function testFailedByInvalidVolumeStepUuid(): void
    {
        // arrange
        $cargoTypeId = '550e8400-e29b-41d4-a716-446655440001';
        $volumeStepId = 'invalid-uuid';
        $totalVolume = 1000;
        $startPrice = 50000;
        $priceStep = 1000;
        $opensAt = strtotime('+1 day');
        $closesAt = strtotime('+2 days');

        $client = $this->createClient();

        // act
        $client->jsonRequest(
            method: 'POST',
            uri: self::ENDPOINT_URL,
            parameters: [
                'cargoTypeId' => $cargoTypeId,
                'totalVolume' => $totalVolume,
                'startPrice' => $startPrice,
                'priceStep' => $priceStep,
                'volumeStepId' => $volumeStepId,
                'opensAt' => $opensAt,
                'closesAt' => $closesAt,
            ]
        );

        $response = $this->getDataFromJsonResponse($client->getResponse());

        // assert
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertEquals('Некорректные данные запроса.', $response['message']);
        self::assertCount(1, $response['errors']);
        self::assertEquals('volumeStepId', $response['errors'][0]['source']);
    }

    public function testFailedByNegativeTotalVolume(): void
    {
        // arrange
        $cargoTypeId = '550e8400-e29b-41d4-a716-446655440001';
        $volumeStepId = '550e8400-e29b-41d4-a716-446655440010';
        $totalVolume = -100;
        $startPrice = 50000;
        $priceStep = 1000;
        $opensAt = strtotime('+1 day');
        $closesAt = strtotime('+2 days');

        $client = $this->createClient();

        // act
        $client->jsonRequest(
            method: 'POST',
            uri: self::ENDPOINT_URL,
            parameters: [
                'cargoTypeId' => $cargoTypeId,
                'totalVolume' => $totalVolume,
                'startPrice' => $startPrice,
                'priceStep' => $priceStep,
                'volumeStepId' => $volumeStepId,
                'opensAt' => $opensAt,
                'closesAt' => $closesAt,
            ]
        );

        $response = $this->getDataFromJsonResponse($client->getResponse());

        // assert
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertEquals('Некорректные данные запроса.', $response['message']);
        self::assertCount(1, $response['errors']);
        self::assertEquals('totalVolume', $response['errors'][0]['source']);
    }

    public function testFailedByNegativeStartPrice(): void
    {
        // arrange
        $cargoTypeId = '550e8400-e29b-41d4-a716-446655440001';
        $volumeStepId = '550e8400-e29b-41d4-a716-446655440010';
        $totalVolume = 1000;
        $startPrice = -50000;
        $priceStep = 1000;
        $opensAt = strtotime('+1 day');
        $closesAt = strtotime('+2 days');

        $client = $this->createClient();

        // act
        $client->jsonRequest(
            method: 'POST',
            uri: self::ENDPOINT_URL,
            parameters: [
                'cargoTypeId' => $cargoTypeId,
                'totalVolume' => $totalVolume,
                'startPrice' => $startPrice,
                'priceStep' => $priceStep,
                'volumeStepId' => $volumeStepId,
                'opensAt' => $opensAt,
                'closesAt' => $closesAt,
            ]
        );

        $response = $this->getDataFromJsonResponse($client->getResponse());

        // assert
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertEquals('Некорректные данные запроса.', $response['message']);
        self::assertCount(1, $response['errors']);
        self::assertEquals('startPrice', $response['errors'][0]['source']);
    }

    public function testFailedByNegativePriceStep(): void
    {
        // arrange
        $cargoTypeId = '550e8400-e29b-41d4-a716-446655440001';
        $volumeStepId = '550e8400-e29b-41d4-a716-446655440010';
        $totalVolume = 1000;
        $startPrice = 50000;
        $priceStep = -1000;
        $opensAt = strtotime('+1 day');
        $closesAt = strtotime('+2 days');

        $client = $this->createClient();

        // act
        $client->jsonRequest(
            method: 'POST',
            uri: self::ENDPOINT_URL,
            parameters: [
                'cargoTypeId' => $cargoTypeId,
                'totalVolume' => $totalVolume,
                'startPrice' => $startPrice,
                'priceStep' => $priceStep,
                'volumeStepId' => $volumeStepId,
                'opensAt' => $opensAt,
                'closesAt' => $closesAt,
            ]
        );

        $response = $this->getDataFromJsonResponse($client->getResponse());

        // assert
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertEquals('Некорректные данные запроса.', $response['message']);
        self::assertCount(1, $response['errors']);
        self::assertEquals('priceStep', $response['errors'][0]['source']);
    }

    public function testFailedByNonExistentCargoType(): void
    {
        // arrange
        $cargoTypeId = '550e8400-e29b-41d4-a716-446655440999';
        $volumeStepId = '550e8400-e29b-41d4-a716-446655440010';
        $totalVolume = 1000;
        $startPrice = 50000;
        $priceStep = 1000;
        $opensAt = strtotime('+1 day');
        $closesAt = strtotime('+2 days');

        $client = $this->createClient();

        // act
        $client->jsonRequest(
            method: 'POST',
            uri: self::ENDPOINT_URL,
            parameters: [
                'cargoTypeId' => $cargoTypeId,
                'totalVolume' => $totalVolume,
                'startPrice' => $startPrice,
                'priceStep' => $priceStep,
                'volumeStepId' => $volumeStepId,
                'opensAt' => $opensAt,
                'closesAt' => $closesAt,
            ]
        );

        $response = $this->getDataFromJsonResponse($client->getResponse());

        // assert
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertEquals('Ошибка бизнес-логики.', $response['message']);
        self::assertEquals('CargoType not found', $response['errors'][0]['detail']);
    }

    public function testFailedByNonExistentVolumeStep(): void
    {
        // arrange
        $cargoTypeId = '550e8400-e29b-41d4-a716-446655440001';
        $volumeStepId = '550e8400-e29b-41d4-a716-446655440999';
        $totalVolume = 1000;
        $startPrice = 50000;
        $priceStep = 1000;
        $opensAt = strtotime('+1 day');
        $closesAt = strtotime('+2 days');

        $client = $this->createClient();

        // act
        $client->jsonRequest(
            method: 'POST',
            uri: self::ENDPOINT_URL,
            parameters: [
                'cargoTypeId' => $cargoTypeId,
                'totalVolume' => $totalVolume,
                'startPrice' => $startPrice,
                'priceStep' => $priceStep,
                'volumeStepId' => $volumeStepId,
                'opensAt' => $opensAt,
                'closesAt' => $closesAt,
            ]
        );

        $response = $this->getDataFromJsonResponse($client->getResponse());

        // assert
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertEquals('Ошибка бизнес-логики.', $response['message']);
        self::assertEquals('VolumeStep not found', $response['errors'][0]['detail']);
    }

    public function testFailedByOpensAtAfterClosesAt(): void
    {
        // arrange
        $cargoTypeId = '550e8400-e29b-41d4-a716-446655440001';
        $volumeStepId = '550e8400-e29b-41d4-a716-446655440010';
        $totalVolume = 1000;
        $startPrice = 50000;
        $priceStep = 1000;
        $opensAt = strtotime('+2 days');
        $closesAt = strtotime('+1 day');

        $client = $this->createClient();

        // act
        $client->jsonRequest(
            method: 'POST',
            uri: self::ENDPOINT_URL,
            parameters: [
                'cargoTypeId' => $cargoTypeId,
                'totalVolume' => $totalVolume,
                'startPrice' => $startPrice,
                'priceStep' => $priceStep,
                'volumeStepId' => $volumeStepId,
                'opensAt' => $opensAt,
                'closesAt' => $closesAt,
            ]
        );

        $response = $this->getDataFromJsonResponse($client->getResponse());

        // assert
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertEquals('Ошибка бизнес-логики.', $response['message']);
        self::assertEquals('Opens at must be before closes at', $response['errors'][0]['detail']);
    }

    public function testFailedByOpensAtEqualsClosesAt(): void
    {
        // arrange
        $cargoTypeId = '550e8400-e29b-41d4-a716-446655440001';
        $volumeStepId = '550e8400-e29b-41d4-a716-446655440010';
        $totalVolume = 1000;
        $startPrice = 50000;
        $priceStep = 1000;
        $timestamp = strtotime('+1 day');
        $opensAt = $timestamp;
        $closesAt = $timestamp;

        $client = $this->createClient();

        // act
        $client->jsonRequest(
            method: 'POST',
            uri: self::ENDPOINT_URL,
            parameters: [
                'cargoTypeId' => $cargoTypeId,
                'totalVolume' => $totalVolume,
                'startPrice' => $startPrice,
                'priceStep' => $priceStep,
                'volumeStepId' => $volumeStepId,
                'opensAt' => $opensAt,
                'closesAt' => $closesAt,
            ]
        );

        $response = $this->getDataFromJsonResponse($client->getResponse());

        // assert
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertEquals('Ошибка бизнес-логики.', $response['message']);
        self::assertEquals('Opens at must be before closes at', $response['errors'][0]['detail']);
    }

    public function testFailedByClosesAtInThePast(): void
    {
        // arrange
        $cargoTypeId = '550e8400-e29b-41d4-a716-446655440001';
        $volumeStepId = '550e8400-e29b-41d4-a716-446655440010';
        $totalVolume = 1000;
        $startPrice = 50000;
        $priceStep = 1000;
        $opensAt = strtotime('-2 days');
        $closesAt = strtotime('-1 day');

        $client = $this->createClient();

        // act
        $client->jsonRequest(
            method: 'POST',
            uri: self::ENDPOINT_URL,
            parameters: [
                'cargoTypeId' => $cargoTypeId,
                'totalVolume' => $totalVolume,
                'startPrice' => $startPrice,
                'priceStep' => $priceStep,
                'volumeStepId' => $volumeStepId,
                'opensAt' => $opensAt,
                'closesAt' => $closesAt,
            ]
        );

        $response = $this->getDataFromJsonResponse($client->getResponse());

        // assert
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertEquals('Ошибка бизнес-логики.', $response['message']);
        self::assertEquals('Closes at must be in the future', $response['errors'][0]['detail']);
    }

    public function testFailedByVolumeNotMultipleOfVolumeStep(): void
    {
        // arrange
        $cargoTypeId = '550e8400-e29b-41d4-a716-446655440001';
        $volumeStepId = '550e8400-e29b-41d4-a716-446655440010';
        $totalVolume = 1003; // Not multiple of 25
        $startPrice = 50000;
        $priceStep = 1000;
        $opensAt = strtotime('+1 day');
        $closesAt = strtotime('+2 days');

        $client = $this->createClient();

        // act
        $client->jsonRequest(
            method: 'POST',
            uri: self::ENDPOINT_URL,
            parameters: [
                'cargoTypeId' => $cargoTypeId,
                'totalVolume' => $totalVolume,
                'startPrice' => $startPrice,
                'priceStep' => $priceStep,
                'volumeStepId' => $volumeStepId,
                'opensAt' => $opensAt,
                'closesAt' => $closesAt,
            ]
        );

        $response = $this->getDataFromJsonResponse($client->getResponse());

        // assert
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertEquals('Ошибка бизнес-логики.', $response['message']);
        self::assertEquals('Total volume must be multiple of volume step (25 tons)', $response['errors'][0]['detail']);
    }
}
