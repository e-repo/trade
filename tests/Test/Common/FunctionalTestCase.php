<?php

declare(strict_types=1);

namespace Test\Common;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Faker\Factory;
use Faker\Generator;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\BrowserKitAssertionsTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Mailer\EventListener\MessageLoggerListener;

class FunctionalTestCase extends KernelTestCase
{
    use BrowserKitAssertionsTrait;

    protected ContainerInterface $container;

    protected Application $application;

    protected EntityManager $entityManager;

    protected QueryBuilder $queryBuilder;

    protected AbstractDatabaseTool $databaseTool;

    protected Generator $faker;

    protected ?MessageLoggerListener $mailerListener;

    public function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->application = new Application($kernel);
        $this->container = static::getContainer();
        $this->faker = Factory::create();

        $this->entityManager = $this->container
            ->get('doctrine')
            ->getManager();

        $this->queryBuilder = $this->entityManager
            ->getConnection()
            ->createQueryBuilder();

        /** @var DatabaseToolCollection $databaseTool */
        $databaseTool = $this->container->get(DatabaseToolCollection::class);
        $this->databaseTool = $databaseTool->get();

        $this->mailerListener = $this->container->get('mailer.message_logger_listener');
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    protected function createClient(array $server = []): ?KernelBrowser
    {
        try {
            $client = $this->container->get('test.client');
        } catch (ServiceNotFoundException) {
            if (class_exists(KernelBrowser::class)) {
                throw new LogicException('You cannot create the client used in functional tests if the "framework.test" config is not set to true.');
            }
            throw new LogicException('You cannot create the client used in functional tests if the BrowserKit component is not available. Try running "composer require symfony/browser-kit".');
        }

        $client->setServerParameters($server);

        return self::getClient($client);
    }
}
