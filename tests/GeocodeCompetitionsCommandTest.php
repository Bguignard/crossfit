<?php

namespace App\Tests;

use App\Command\GeocodeCompetitionsCommand;
use App\Entity\Competition\Competition;
use App\Entity\Competition\CompetitionGeocodingCache;
use App\Services\Competition\CompetitionExternalGeocoderInterface;
use App\Services\Competition\CompetitionGeoNormalizer;
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

    public function testItRetriesCachedUnresolvedLocationWithExternalGeocoder(): void
    {
        $rawLocation = 'Woolston, christchurch, New Zealand, New zealand';
        $competition = (new Competition('Battle NZ', 'competition_corner', 'geocode-nz'))
            ->setLocationLabel($rawLocation)
            ->setIsOnline(false);
        $cache = (new CompetitionGeocodingCache(
            hash('sha256', mb_strtolower($rawLocation)),
            $rawLocation,
            'local_normalizer',
        ))->markUnresolved('Local resolver could not derive enough structured geography.');

        $this->getEntityManager()->persist($competition);
        $this->getEntityManager()->persist($cache);
        $this->getEntityManager()->flush();

        $command = new GeocodeCompetitionsCommand(
            $this->getEntityManager(),
            new CompetitionGeoNormalizer(),
            new class implements CompetitionExternalGeocoderInterface {
                public function resolve(string $rawLocation): ?array
                {
                    return [
                        'provider' => 'test_external',
                        'confidence' => 0.9,
                        'geo' => [
                            'countryName' => 'New Zealand',
                            'countryCode' => 'NZ',
                            'regionName' => 'Canterbury',
                            'departmentName' => null,
                            'cityName' => 'Christchurch',
                            'latitude' => -43.5505,
                            'longitude' => 172.6811,
                        ],
                    ];
                }
            },
        );

        $tester = new CommandTester($command);
        self::assertSame(Command::SUCCESS, $tester->execute(['--limit' => 50, '--retry-unresolved' => true, '--write' => true]));
        $this->getEntityManager()->clear();

        /** @var Competition|null $updatedCompetition */
        $updatedCompetition = $this->getRepository(Competition::class)->findOneBy(['externalId' => 'geocode-nz']);
        /** @var CompetitionGeocodingCache|null $updatedCache */
        $updatedCache = $this->getRepository(CompetitionGeocodingCache::class)->findOneBy(['rawLocationHash' => hash('sha256', mb_strtolower($rawLocation))]);

        self::assertNotNull($updatedCompetition);
        self::assertNotNull($updatedCache);
        self::assertSame('New Zealand', $updatedCompetition->getCountryName());
        self::assertSame('NZ', $updatedCompetition->getCountryCode());
        self::assertSame('Canterbury', $updatedCompetition->getRegionName());
        self::assertSame('Christchurch', $updatedCompetition->getCityName());
        self::assertTrue($updatedCache->isResolved());
        self::assertSame('test_external', $updatedCache->getProvider());
    }

    public function testItRefreshesResolvedCacheWhenGeoFieldsArePolluted(): void
    {
        $rawLocation = 'CrossFit 696<br />696 W Broadway<br />Gardner, MA, 01440, United States';
        $hash = hash('sha256', mb_strtolower(html_entity_decode($rawLocation, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $competition = (new Competition('Battle USA', 'competition_corner', 'geocode-usa'))
            ->setLocationLabel($rawLocation)
            ->setIsOnline(false);
        $cache = (new CompetitionGeocodingCache($hash, html_entity_decode($rawLocation, ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'local_normalizer'))
            ->markResolved([
                'countryName' => 'United States',
                'countryCode' => 'US',
                'regionName' => 'MA, 01440',
                'departmentName' => null,
                'cityName' => 'CrossFit 696<br />696 W Broadway<br />Gardner',
                'latitude' => null,
                'longitude' => null,
            ], 0.7);

        $this->getEntityManager()->persist($competition);
        $this->getEntityManager()->persist($cache);
        $this->getEntityManager()->flush();

        $command = new GeocodeCompetitionsCommand(
            $this->getEntityManager(),
            new CompetitionGeoNormalizer(),
            new class implements CompetitionExternalGeocoderInterface {
                public function resolve(string $rawLocation): ?array
                {
                    return [
                        'provider' => 'test_external',
                        'confidence' => 0.9,
                        'geo' => [
                            'countryName' => 'United States',
                            'countryCode' => 'US',
                            'regionName' => 'Massachusetts',
                            'departmentName' => 'Worcester County',
                            'cityName' => 'Gardner',
                            'latitude' => 42.5751,
                            'longitude' => -71.9981,
                        ],
                    ];
                }
            },
        );

        $tester = new CommandTester($command);
        self::assertSame(Command::SUCCESS, $tester->execute(['--limit' => 50, '--write' => true]));
        $this->getEntityManager()->clear();

        /** @var Competition|null $updatedCompetition */
        $updatedCompetition = $this->getRepository(Competition::class)->findOneBy(['externalId' => 'geocode-usa']);
        /** @var CompetitionGeocodingCache|null $updatedCache */
        $updatedCache = $this->getRepository(CompetitionGeocodingCache::class)->findOneBy(['rawLocationHash' => $hash]);

        self::assertNotNull($updatedCompetition);
        self::assertNotNull($updatedCache);
        self::assertSame('Gardner', $updatedCompetition->getCityName());
        self::assertSame('Massachusetts', $updatedCompetition->getRegionName());
        self::assertSame('Worcester County', $updatedCompetition->getDepartmentName());
        self::assertSame('test_external', $updatedCache->getProvider());
        self::assertSame('Gardner', $updatedCache->geo()['cityName']);
    }
}
