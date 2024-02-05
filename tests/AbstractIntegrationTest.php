<?php

namespace App\Tests;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractIntegrationTest extends WebTestCase
{
    protected ?EntityManager $entityManager = null;
    protected ReferenceRepository $referenceRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->referenceRepository = new ReferenceRepository($this->entityManager);
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

    protected function getReference(string $name, string $class = null): object
    {
        return $this->getReferenceRepository()->getReference($name, $class);
    }

    protected function getReferenceRepository(): ReferenceRepository
    {
        return $this->referenceRepository;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
