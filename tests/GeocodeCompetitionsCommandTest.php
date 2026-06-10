<?php

namespace App\Tests;

use App\Command\GeocodeCompetitionsCommand;
use App\Entity\Competition\Competition;
use App\Entity\Competition\CompetitionGeocodingCache;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class GeocodeCompetitionsCommandTest extends AbstractIntegrationTest
{
    public function testItReusesCacheCreatedEarlierInSameBatch(): void
    {
        $competitionA = (new Competition('Battle A', 'competition_corner', 'geocode-a'))
            ->setLocationLabel('CrossFit Louviers<br />10 Rue des Entrepots<br />Louviers, Eure, 27400, France')
            ->setIsOnline(false);
        $competitionB = (new Competition('Battle B', 'competition_corner', 'geocode-b'))
            ->setLocationLabel('CrossFit Louviers<br />10 Rue des Entrepots<br />Louviers, Eure, 27400, France')
            ->setIsOnline(false);

        $this->getEntityManager()->persist($competitionA);
        $this->getEntityManager()->persist($competitionB);
        $this->getEntityManager()->flush();

        $command = $this->getService(GeocodeCompetitionsCommand::class);
        self::assertInstanceOf(GeocodeCompetitionsCommand::class, $command);

        $tester = new CommandTester($command);
        self::assertSame(Command::SUCCESS, $tester->execute(['--limit' => 50, '--write' => true]));
        $this->getEntityManager()->clear();

        self::assertCount(1, $this->getRepository(CompetitionGeocodingCache::class)->findAll());

        /** @var Competition|null $updatedA */
        $updatedA = $this->getRepository(Competition::class)->findOneBy(['externalId' => 'geocode-a']);
        /** @var Competition|null $updatedB */
        $updatedB = $this->getRepository(Competition::class)->findOneBy(['externalId' => 'geocode-b']);

        self::assertNotNull($updatedA);
        self::assertNotNull($updatedB);
        self::assertSame('Normandie', $updatedA->getRegionName());
        self::assertSame('Normandie', $updatedB->getRegionName());
    }
}
