<?php

declare(strict_types=1);

namespace Test\Integration\SomeModule\Api\GetListCategory;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use JsonException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Test\Common\DataFromJsonResponseTrait;
use Test\Common\FunctionalTestCase;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

final class GetListCategoryTest extends FunctionalTestCase
{
    use DataFromJsonResponseTrait;
    use InteractsWithMessenger;

    private const ENDPOINT_URL = '/api/v1/module/categories';

    private KernelBrowser $client;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createClient();

        $this->databaseTool
            ->withPurgeMode(ORMPurger::PURGE_MODE_TRUNCATE)
            ->loadFixtures([
                CategoryFixture::class,
            ]);
    }

    /**
     * @throws JsonException
     */
    public function testSuccessGetCategories(): void
    {
        // arrange
        $offset = 0;
        $limit = 10;

        $expectedFields = [
            'id',
            'name',
            'description',
            'createdAt',
        ];

        $expectedUserIds = array_map(
            static fn (array $category) => $category['id'],
            CategoryFixture::allItems()
        );

        // action
        $this->client->request(
            method: 'GET',
            uri: self::ENDPOINT_URL,
            parameters: [
                'offset' => $offset,
                'limit' => $limit,
            ]
        );

        $response = $this->getDataFromJsonResponse($this->client->getResponse());

        // assert
        self::assertResponseIsSuccessful();
        self::assertEquals($offset, $response['meta']['offset']);
        self::assertEquals($limit, $response['meta']['limit']);

        self::assertCount($response['meta']['total'], CategoryFixture::allItems());

        foreach ($response['data'] as $userResponse) {
            self::assertEqualsCanonicalizing($expectedFields, array_keys($userResponse));

            self::assertContains($userResponse['id'], $expectedUserIds);
        }
    }

    public function testSuccessByNameFilter(): void
    {
        // arrange
        $offset = 0;
        $limit = 10;

        $expectedCategory = [
            'id' => '28912aa1-96ee-4631-8e34-14cd2f019e53',
            'name' => 'Категория 1',
            'description' => 'Категория 1 содержит статьи на тему...',
        ];

        // action
        $this->client->request(
            method: 'GET',
            uri: self::ENDPOINT_URL,
            parameters: [
                'name' => 'Категория 1',
                'offset' => $offset,
                'limit' => $limit,
            ]
        );

        $categoryResponse = $this->getDataFromJsonResponse($this->client->getResponse());

        // assert
        self::assertResponseIsSuccessful();
        self::assertEquals(1, $categoryResponse['meta']['total']);
        self::assertCount(1, $categoryResponse['data']);

        $category = reset($categoryResponse['data']);

        self::assertEquals($expectedCategory['id'], $category['id']);
        self::assertEquals($expectedCategory['name'], $category['name']);
        self::assertEquals($expectedCategory['description'], $category['description']);
    }

    public function testSuccessByPartNameFilter(): void
    {
        // arrange
        $offset = 0;
        $limit = 10;

        // action
        $this->client->request(
            method: 'GET',
            uri: self::ENDPOINT_URL,
            parameters: [
                'name' => 'Категор',
                'offset' => $offset,
                'limit' => $limit,
            ]
        );

        $categoryResponse = $this->getDataFromJsonResponse($this->client->getResponse());

        // assert
        self::assertResponseIsSuccessful();
        self::assertCount(5, $categoryResponse['data']);
    }

    /**
     * @throws JsonException
     */
    public function testFailedWithoutOffset(): void
    {
        // arrange
        $limit = 10;

        // action
        $this->client->request(
            method: 'GET',
            uri: self::ENDPOINT_URL,
            parameters: [
                'limit' => $limit,
            ]
        );

        $response = $this->getDataFromJsonResponse($this->client->getResponse());
        $errors = $response['errors'];

        // assert
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertEquals('Не заполнено поле offset', $errors[0]['detail']);
    }

    /**
     * @throws JsonException
     */
    public function testFailedWithoutLimit(): void
    {
        // arrange
        $offset = 0;

        // action
        $this->client->request(
            method: 'GET',
            uri: self::ENDPOINT_URL,
            parameters: [
                'offset' => $offset,
            ]
        );

        $response = $this->getDataFromJsonResponse($this->client->getResponse());
        $errors = $response['errors'];

        // assert
        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertEquals('Не заполнено поле limit', $errors[0]['detail']);
    }
}
