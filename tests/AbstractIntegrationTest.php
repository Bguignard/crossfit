<?php

namespace App\Tests;

use Doctrine\Bundle\FixturesBundle\Loader\SymfonyFixturesLoader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractIntegrationTest extends WebTestCase
{
    protected ?EntityManager $entityManager = null;
    protected ReferenceRepository $referenceRepository;
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->entityManager = static::getContainer()
            ->get('doctrine')
            ->getManager();

        // Load all data fixtures for integration tests
        /** @var SymfonyFixturesLoader $fixturesLoader */
        $fixturesLoader = static::getContainer()->get('doctrine.fixtures.loader');
        $fixtures = $fixturesLoader->getFixtures();

        $purger = new ORMPurger($this->entityManager);
        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->purge();
        $executor->execute($fixtures, true);

        $this->referenceRepository = $executor->getReferenceRepository();
    }

    protected function getService(string $serviceName): object
    {
        return static::getContainer()->get($serviceName);
    }

    public function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    public function getRepository(string $repositoryClassName): ObjectRepository
    {
        return $this->getEntityManager()->getRepository($repositoryClassName);
    }

    protected function getReference(string $name, ?string $class = null): object
    {
        return $this->getReferenceRepository()->getReference($name, $class);
    }

    protected function getReferenceRepository(): ReferenceRepository
    {
        return $this->referenceRepository;
    }

    protected function browser(): KernelBrowser
    {
        return $this->client;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->entityManager !== null) {
            $this->entityManager->close();
        }
        $this->entityManager = null;
    }
}
