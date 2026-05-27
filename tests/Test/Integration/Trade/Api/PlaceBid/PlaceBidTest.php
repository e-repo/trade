<?php

declare(strict_types=1);

namespace Test\Integration\Trade\Api\PlaceBid;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Component\HttpFoundation\Response;
use Test\Common\DataFromJsonResponseTrait;
use Test\Common\FunctionalTestCase;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

final class PlaceBidTest extends FunctionalTestCase
{
    use DataFromJsonResponseTrait;
    use InteractsWithMessenger;

    private const ENDPOINT_URL = '/api/v1/trade/bid';
    private const LOT_ID = '550e8400-e29b-41d4-a716-446655440050';
    private const CONTRACTOR_ID = '550e8400-e29b-41d4-a716-446655440020';
    private const CONTRACTOR_ID_2 = '550e8400-e29b-41d4-a716-446655440021';

    public function setUp(): void
    {
        parent::setUp();

        $this->databaseTool
            ->withPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE)
            ->loadFixtures([
                CargoTypeFixture::class,
                VolumeStepFixture::class,
                ContractorFixture::class,
                LotFixture::class,
            ]);
    }

    public function testSuccessPlaceBid(): void
    {
        // arrange
        $requestedVolume = 100;
        $pricePerTon = 45000;

        $client = $this->createClient();

        // act
        $client->jsonRequest(
            method: 'POST',
            uri: self::ENDPOINT_URL,
            parameters: [
                'lotId' => self::LOT_ID,
                'requestedVolume' => $requestedVolume,
                'pricePerTon' => $pricePerTon,
            ],
            server: [
                'HTTP_X_USER_ID' => self::CONTRACTOR_ID,
            ]
        );

        $response = $this->getDataFromJsonResponse($client->getResponse());

        // assert
        self::assertResponseIsSuccessful();
        self::assertArrayHasKey('data', $response);
        self::assertArrayHasKey('bidId', $response['data']);
        self::assertArrayHasKey('status', $response['data']);
        self::assertArrayHasKey('allocatedVolume', $response['data']);
        self::assertArrayHasKey('requestedVolume', $response['data']);

        self::assertEquals('ACTIVE', $response['data']['status']);
        self::assertEquals($requestedVolume, $response['data']['requestedVolume']);
        self::assertEquals($requestedVolume, $response['data']['allocatedVolume']);
        self::assertNotEmpty($response['data']['bidId']);

        // verify bid was created in database using DQL
        $query = $this->entityManager->createQuery('SELECT COUNT(b.id) FROM Trade\Domain\Lot\Entity\Bid b');
        $count = $query->getSingleScalarResult();
        self::assertEquals(1, $count);

        // verify lot reserved volume was updated
        $lotQuery = $this->entityManager->createQuery(
            'SELECT l.volume.reservedVolume FROM Trade\Domain\Lot\Entity\Lot l WHERE l.id = :lotId'
        );
        $lotQuery->setParameter('lotId', self::LOT_ID);
        $reservedVolume = $lotQuery->getSingleScalarResult();
        self::assertEquals($requestedVolume, $reservedVolume);
    }

    public function testSuccessPlaceBidWithPartialAllocation(): void
    {
        // arrange: pre-fill lot with 800 tons at better price (40000), leaving only 200 tons free
        $this->placeBid(lotId: self::LOT_ID, contractorId: self::CONTRACTOR_ID_2, volume: 800, price: 40000);

        $requestedVolume = 300;
        $pricePerTon = 45000;

        $client = $this->createClient();

        // act
        $client->jsonRequest(
            method: 'POST',
            uri: self::ENDPOINT_URL,
            parameters: [
                'lotId' => self::LOT_ID,
                'requestedVolume' => $requestedVolume,
                'pricePerTon' => $pricePerTon,
            ],
            server: [
                'HTTP_X_USER_ID' => self::CONTRACTOR_ID,
            ]
        );

        $response = $this->getDataFromJsonResponse($client->getResponse());

        // assert
        self::assertResponseIsSuccessful();
        self::assertEquals('PARTIALLY_ACTIVE', $response['data']['status']);
        self::assertEquals($requestedVolume, $response['data']['requestedVolume']);
        self::assertEquals(200, $response['data']['allocatedVolume']);

        // verify lot is fully reserved now (800 + 200 = 1000)
        $lotQuery = $this->entityManager->createQuery(
            'SELECT l.volume.reservedVolume FROM Trade\Domain\Lot\Entity\Lot l WHERE l.id = :lotId'
        );
        $lotQuery->setParameter('lotId', self::LOT_ID);
        $reservedVolume = $lotQuery->getSingleScalarResult();
        self::assertEquals(1000, $reservedVolume);
    }

    public function testSuccessPlaceBidDisplacingWorseOnes(): void
    {
        // arrange: create bids with worse (higher) price that fill the lot completely
        $bid1Id = $this->placeBid(lotId: self::LOT_ID, contractorId: self::CONTRACTOR_ID_2, volume: 500, price: 50000);
        $bid2Id = $this->placeBid(lotId: self::LOT_ID, contractorId: self::CONTRACTOR_ID_2, volume: 500, price: 51000);

        $requestedVolume = 600;
        $pricePerTon = 45000;

        $client = $this->createClient();

        // act: place better (lower) price bid that should displace both old bids
        $client->jsonRequest(
            method: 'POST',
            uri: self::ENDPOINT_URL,
            parameters: [
                'lotId' => self::LOT_ID,
                'requestedVolume' => $requestedVolume,
                'pricePerTon' => $pricePerTon,
            ],
            server: [
                'HTTP_X_USER_ID' => self::CONTRACTOR_ID,
            ]
        );

        $response = $this->getDataFromJsonResponse($client->getResponse());

        // assert: new bid is ACTIVE
        self::assertResponseIsSuccessful();
        self::assertEquals('ACTIVE', $response['data']['status']);
        self::assertEquals($requestedVolume, $response['data']['allocatedVolume']);

        // verify most expensive bid (51000) got OUTBID status
        $bidQuery = $this->entityManager->createQuery(
            'SELECT b.status, b.allocatedVolume FROM Trade\Domain\Lot\Entity\Bid b WHERE b.id = :bidId'
        );
        $bidQuery->setParameter('bidId', $bid2Id);
        $displacedBid = $bidQuery->getSingleResult();
        self::assertEquals('OUTBID', $displacedBid['status']->value);
        self::assertEquals(0, $displacedBid['allocatedVolume']);

        // verify second bid (50000) got partially displaced (500 -> 400)
        $bidQuery->setParameter('bidId', $bid1Id);
        $partiallyDisplaced = $bidQuery->getSingleResult();
        self::assertEquals('PARTIALLY_ACTIVE', $partiallyDisplaced['status']->value);
        self::assertEquals(400, $partiallyDisplaced['allocatedVolume']);

        // verify lot reserved volume is correct (400 old + 600 new = 1000)
        $lotQuery = $this->entityManager->createQuery(
            'SELECT l.volume.reservedVolume FROM Trade\Domain\Lot\Entity\Lot l WHERE l.id = :lotId'
        );
        $lotQuery->setParameter('lotId', self::LOT_ID);
        $reservedVolume = $lotQuery->getSingleScalarResult();
        self::assertEquals(1000, $reservedVolume);
    }

    public function testSuccessPlaceBidPartialDisplacement(): void
    {
        // arrange: fill lot completely with bids at higher price
        $expensiveBidId = $this->placeBid(lotId: self::LOT_ID, contractorId: self::CONTRACTOR_ID_2, volume: 500, price: 52000);
        $this->placeBid(lotId: self::LOT_ID, contractorId: self::CONTRACTOR_ID_2, volume: 500, price: 50000);

        $requestedVolume = 300;
        $pricePerTon = 45000;

        $client = $this->createClient();

        // act: place better price bid for 300 tons
        $client->jsonRequest(
            method: 'POST',
            uri: self::ENDPOINT_URL,
            parameters: [
                'lotId' => self::LOT_ID,
                'requestedVolume' => $requestedVolume,
                'pricePerTon' => $pricePerTon,
            ],
            server: [
                'HTTP_X_USER_ID' => self::CONTRACTOR_ID,
            ]
        );

        $response = $this->getDataFromJsonResponse($client->getResponse());

        // assert: new bid is ACTIVE
        self::assertResponseIsSuccessful();
        self::assertEquals('ACTIVE', $response['data']['status']);
        self::assertEquals($requestedVolume, $response['data']['allocatedVolume']);

        // verify most expensive bid was partially displaced (500 -> 200)
        $bidQuery = $this->entityManager->createQuery(
            'SELECT b.status, b.allocatedVolume, b.requestedVolume FROM Trade\Domain\Lot\Entity\Bid b WHERE b.id = :bidId'
        );
        $bidQuery->setParameter('bidId', $expensiveBidId);
        $partiallyDisplacedBid = $bidQuery->getSingleResult();
        self::assertEquals('PARTIALLY_ACTIVE', $partiallyDisplacedBid['status']->value);
        self::assertEquals(200, $partiallyDisplacedBid['allocatedVolume']);
        self::assertEquals(500, $partiallyDisplacedBid['requestedVolume']);

        // verify lot reserved volume (200 expensive + 500 cheaper + 300 new = 1000)
        $lotQuery = $this->entityManager->createQuery(
            'SELECT l.volume.reservedVolume FROM Trade\Domain\Lot\Entity\Lot l WHERE l.id = :lotId'
        );
        $lotQuery->setParameter('lotId', self::LOT_ID);
        $reservedVolume = $lotQuery->getSingleScalarResult();
        self::assertEquals(1000, $reservedVolume);
    }

    public function testSuccessPlaceBidWithSamePriceLIFO(): void
    {
        // arrange: create 2 bids with same price (600 tons total), fill lot completely
        $this->placeBid(lotId: self::LOT_ID, contractorId: self::CONTRACTOR_ID_2, volume: 500, price: 45000);
        $this->placeBid(lotId: self::LOT_ID, contractorId: self::CONTRACTOR_ID_2, volume: 500, price: 45000);

        $requestedVolume = 300;
        $pricePerTon = 45000;

        $client = $this->createClient();

        // act: try to place bid with same price
        $client->jsonRequest(
            method: 'POST',
            uri: self::ENDPOINT_URL,
            parameters: [
                'lotId' => self::LOT_ID,
                'requestedVolume' => $requestedVolume,
                'pricePerTon' => $pricePerTon,
            ],
            server: [
                'HTTP_X_USER_ID' => self::CONTRACTOR_ID,
            ]
        );

        $response = $this->getDataFromJsonResponse($client->getResponse());

        // assert: bid is REJECTED (cannot displace same price, LIFO)
        self::assertResponseIsSuccessful();
        self::assertEquals('REJECTED', $response['data']['status']);
        self::assertEquals($requestedVolume, $response['data']['requestedVolume']);
        self::assertEquals(0, $response['data']['allocatedVolume']);

        // verify lot reserved volume unchanged (still 1000)
        $lotQuery = $this->entityManager->createQuery(
            'SELECT l.volume.reservedVolume FROM Trade\Domain\Lot\Entity\Lot l WHERE l.id = :lotId'
        );
        $lotQuery->setParameter('lotId', self::LOT_ID);
        $reservedVolume = $lotQuery->getSingleScalarResult();
        self::assertEquals(1000, $reservedVolume);
    }

    public function testSuccessPlaceBidForEntireLotVolume(): void
    {
        // arrange: empty lot (1000 tons available)
        $requestedVolume = 1000;
        $pricePerTon = 45000;

        $client = $this->createClient();

        // act: place bid for entire lot volume
        $client->jsonRequest(
            method: 'POST',
            uri: self::ENDPOINT_URL,
            parameters: [
                'lotId' => self::LOT_ID,
                'requestedVolume' => $requestedVolume,
                'pricePerTon' => $pricePerTon,
            ],
            server: [
                'HTTP_X_USER_ID' => self::CONTRACTOR_ID,
            ]
        );

        $response = $this->getDataFromJsonResponse($client->getResponse());

        // assert: bid is fully allocated
        self::assertResponseIsSuccessful();
        self::assertEquals('ACTIVE', $response['data']['status']);
        self::assertEquals($requestedVolume, $response['data']['requestedVolume']);
        self::assertEquals($requestedVolume, $response['data']['allocatedVolume']);

        // verify lot is fully reserved
        $lotQuery = $this->entityManager->createQuery(
            'SELECT l.volume.reservedVolume FROM Trade\Domain\Lot\Entity\Lot l WHERE l.id = :lotId'
        );
        $lotQuery->setParameter('lotId', self::LOT_ID);
        $reservedVolume = $lotQuery->getSingleScalarResult();
        self::assertEquals(1000, $reservedVolume);
    }

    public function testSuccessMultipleBidsFromSameContractor(): void
    {
        // arrange & act: place 2 bids from same contractor
        $client = $this->createClient();

        // first bid: 300 tons
        $client->jsonRequest(
            method: 'POST',
            uri: self::ENDPOINT_URL,
            parameters: [
                'lotId' => self::LOT_ID,
                'requestedVolume' => 300,
                'pricePerTon' => 45000,
            ],
            server: [
                'HTTP_X_USER_ID' => self::CONTRACTOR_ID,
            ]
        );

        $response1 = $this->getDataFromJsonResponse($client->getResponse());
        self::assertResponseIsSuccessful();
        self::assertEquals('ACTIVE', $response1['data']['status']);

        // second bid: 200 tons
        $client->jsonRequest(
            method: 'POST',
            uri: self::ENDPOINT_URL,
            parameters: [
                'lotId' => self::LOT_ID,
                'requestedVolume' => 200,
                'pricePerTon' => 44000,
            ],
            server: [
                'HTTP_X_USER_ID' => self::CONTRACTOR_ID,
            ]
        );

        $response2 = $this->getDataFromJsonResponse($client->getResponse());

        // assert: both bids are successful
        self::assertResponseIsSuccessful();
        self::assertEquals('ACTIVE', $response2['data']['status']);
        self::assertEquals(200, $response2['data']['allocatedVolume']);

        // verify both bids exist in database
        $query = $this->entityManager->createQuery(
            'SELECT COUNT(b.id) FROM Trade\Domain\Lot\Entity\Bid b WHERE b.contractor = :contractorId'
        );
        $query->setParameter('contractorId', self::CONTRACTOR_ID);
        $count = $query->getSingleScalarResult();
        self::assertEquals(2, $count);

        // verify lot reserved volume (300 + 200 = 500)
        $lotQuery = $this->entityManager->createQuery(
            'SELECT l.volume.reservedVolume FROM Trade\Domain\Lot\Entity\Lot l WHERE l.id = :lotId'
        );
        $lotQuery->setParameter('lotId', self::LOT_ID);
        $reservedVolume = $lotQuery->getSingleScalarResult();
        self::assertEquals(500, $reservedVolume);
    }

    private function placeBid(string $lotId, string $contractorId, int $volume, int $price): string
    {
        $client = $this->createClient();
        $client->jsonRequest(
            method: 'POST',
            uri: self::ENDPOINT_URL,
            parameters: [
                'lotId' => $lotId,
                'requestedVolume' => $volume,
                'pricePerTon' => $price,
            ],
            server: [
                'HTTP_X_USER_ID' => $contractorId,
            ]
        );

        $response = $this->getDataFromJsonResponse($client->getResponse());
        self::assertResponseIsSuccessful();

        return $response['data']['bidId'];
    }
}
