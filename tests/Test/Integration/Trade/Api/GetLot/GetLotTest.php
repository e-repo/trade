<?php

declare(strict_types=1);

namespace Test\Integration\Trade\Api\GetLot;

use Carbon\Carbon;
use CoreKit\Domain\Entity\Id;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Component\HttpFoundation\Response;
use Test\Common\DataFromJsonResponseTrait;
use Test\Common\FunctionalTestCase;
use Trade\Domain\Lot\Entity\Lot;
use Trade\Domain\Lot\Enum\CloseReasonEnum;

final class GetLotTest extends FunctionalTestCase
{
    use DataFromJsonResponseTrait;

    private const LOT_ID_OPEN = '550e8400-e29b-41d4-a716-446655440070';
    private const LOT_ID_CLOSED_WITH_WINNERS = '550e8400-e29b-41d4-a716-446655440071';

    public function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::now()->subDays(3));

        $this->databaseTool
            ->withPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE)
            ->loadFixtures([
                CargoTypeFixture::class,
                VolumeStepFixture::class,
                ContractorFixture::class,
                LotFixture::class,
                BidFixture::class,
            ]);

        Carbon::setTestNow();
    }

    public function testSuccessGetOpenLot(): void
    {
        $client = $this->createClient();

        $client->request('GET', '/api/v1/trade/lot/' . self::LOT_ID_OPEN);

        $response = $this->getDataFromJsonResponse($client->getResponse());

        self::assertResponseIsSuccessful();
        self::assertArrayHasKey('data', $response);

        $data = $response['data'];
        self::assertEquals(self::LOT_ID_OPEN, $data['lotId']);
        self::assertEquals('OPEN', $data['status']);
        self::assertEquals(1000, $data['totalVolume']);
        self::assertEquals(50000, $data['startPrice']);
        self::assertEquals(1000, $data['priceStep']);
        self::assertIsInt($data['opensAt']);
        self::assertIsInt($data['closesAt']);
        self::assertNull($data['closeReason']);
        self::assertIsArray($data['winnerContractorIds']);
        self::assertEmpty($data['winnerContractorIds']);
    }

    public function testSuccessGetClosedLotWithWinners(): void
    {
        $lot = $this->entityManager->find(Lot::class, new Id(self::LOT_ID_CLOSED_WITH_WINNERS));
        $lot->close(CloseReasonEnum::EXPIRED);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $client = $this->createClient();

        $client->request('GET', '/api/v1/trade/lot/' . self::LOT_ID_CLOSED_WITH_WINNERS);

        $response = $this->getDataFromJsonResponse($client->getResponse());

        self::assertResponseIsSuccessful();
        self::assertArrayHasKey('data', $response);

        $data = $response['data'];
        self::assertEquals(self::LOT_ID_CLOSED_WITH_WINNERS, $data['lotId']);
        self::assertEquals('CLOSED', $data['status']);
        self::assertEquals('EXPIRED', $data['closeReason']);
        self::assertIsArray($data['winnerContractorIds']);
        self::assertCount(2, $data['winnerContractorIds']);
        self::assertContains('550e8400-e29b-41d4-a716-446655440020', $data['winnerContractorIds']);
        self::assertContains('550e8400-e29b-41d4-a716-446655440021', $data['winnerContractorIds']);
    }

    public function testFailedLotNotFound(): void
    {
        $client = $this->createClient();

        $client->request('GET', '/api/v1/trade/lot/550e8400-e29b-41d4-a716-999999999999');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testFailedInvalidUuid(): void
    {
        $client = $this->createClient();

        $client->request('GET', '/api/v1/trade/lot/invalid-uuid');

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
