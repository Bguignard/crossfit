<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Competition\Competition;
use App\Entity\Competition\CompetitionGeocodingCache;
use App\Services\Competition\CompetitionGeoNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:competitions:geocode',
    description: 'Resolve and cache structured geography for competitions.',
)]
final class GeocodeCompetitionsCommand extends Command
{
    private const PROVIDER_LOCAL_NORMALIZER = 'local_normalizer';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CompetitionGeoNormalizer $competitionGeoNormalizer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum number of competitions to scan.', 500);
        $this->addOption('source', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Restrict to one or more source names.');
        $this->addOption('include-complete', null, InputOption::VALUE_NONE, 'Also refresh competitions that already look geocoded.');
        $this->addOption('write', null, InputOption::VALUE_NONE, 'Persist competition and cache changes. Without this option, the command only reports what would change.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = max(1, (int) $input->getOption('limit'));
        $sources = array_values(array_filter(array_map('strval', (array) $input->getOption('source'))));
        $includeComplete = (bool) $input->getOption('include-complete');
        $write = (bool) $input->getOption('write');

        $queryBuilder = $this->entityManager->getRepository(Competition::class)
            ->createQueryBuilder('competition')
            ->where('competition.locationLabel IS NOT NULL')
            ->andWhere('competition.isOnline IS NULL OR competition.isOnline = false')
            ->orderBy('competition.updatedAt', 'DESC')
            ->setMaxResults($limit);

        if (count($sources) > 0) {
            $queryBuilder
                ->andWhere('competition.sourceName IN (:sources)')
                ->setParameter('sources', $sources);
        }

        /** @var list<Competition> $competitions */
        $competitions = $queryBuilder->getQuery()->getResult();
        $processed = 0;
        $cacheCreated = 0;
        $cacheHits = 0;
        $resolved = 0;
        $unresolved = 0;
        $wouldUpdate = 0;
        $rows = [];
        /** @var array<string, CompetitionGeocodingCache> $cacheByHash */
        $cacheByHash = [];

        foreach ($competitions as $competition) {
            if (!$includeComplete && !$this->needsGeocoding($competition)) {
                continue;
            }

            $rawLocation = $this->normalizedRawLocation($competition->getLocationLabel());
            if ($rawLocation === null) {
                continue;
            }

            ++$processed;
            $hash = hash('sha256', mb_strtolower($rawLocation));
            $cache = $cacheByHash[$hash]
                ?? $this->entityManager->getRepository(CompetitionGeocodingCache::class)->findOneBy(['rawLocationHash' => $hash]);
            if ($cache instanceof CompetitionGeocodingCache) {
                ++$cacheHits;
                if ($write) {
                    $cache->markUsed();
                }
            } else {
                $cache = new CompetitionGeocodingCache($hash, $rawLocation, self::PROVIDER_LOCAL_NORMALIZER);
                ++$cacheCreated;
                $geo = $this->competitionGeoNormalizer->fromImportRow([
                    'locationLabel' => $rawLocation,
                    'isOnline' => $competition->isOnline(),
                    'countryName' => $competition->getCountryName(),
                    'countryCode' => $competition->getCountryCode(),
                    'regionName' => $competition->getRegionName(),
                    'departmentName' => $competition->getDepartmentName(),
                    'cityName' => $competition->getCityName(),
                    'latitude' => $competition->getLatitude(),
                    'longitude' => $competition->getLongitude(),
                ]);

                if ($this->isResolved($geo)) {
                    $cache->markResolved($geo, $this->confidence($geo));
                } else {
                    $cache->markUnresolved('Local resolver could not derive enough structured geography.');
                }

                if ($write) {
                    $this->entityManager->persist($cache);
                }
            }
            $cacheByHash[$hash] = $cache;

            if (!$cache->isResolved()) {
                ++$unresolved;
                if (count($rows) < 20) {
                    $rows[] = [$competition->getName(), $rawLocation, '-', '-', 'unresolved'];
                }
                continue;
            }

            ++$resolved;
            $geo = $cache->geo();
            if (!$this->hasGeoChange($competition, $geo)) {
                continue;
            }

            ++$wouldUpdate;
            if (count($rows) < 20) {
                $rows[] = [
                    $competition->getName(),
                    $rawLocation,
                    $geo['cityName'] ?? '-',
                    $geo['regionName'] ?? '-',
                    'resolved',
                ];
            }

            if ($write) {
                $competition
                    ->setCountryName($geo['countryName'])
                    ->setCountryCode($geo['countryCode'])
                    ->setRegionName($geo['regionName'])
                    ->setDepartmentName($geo['departmentName'])
                    ->setCityName($geo['cityName'])
                    ->setLatitude($geo['latitude'])
                    ->setLongitude($geo['longitude']);
            }
        }

        if ($write) {
            $this->entityManager->flush();
        }

        $io->title('Competition geocoding');
        $io->definitionList(
            ['scanned' => count($competitions)],
            ['processed' => $processed],
            ['cache_hits' => $cacheHits],
            [$write ? 'cache_created' : 'would_create_cache' => $cacheCreated],
            ['resolved' => $resolved],
            ['unresolved' => $unresolved],
            [$write ? 'updated_competitions' : 'would_update_competitions' => $wouldUpdate],
        );
        if (count($rows) > 0) {
            $io->table(['Competition', 'Location', 'City', 'Region', 'Status'], $rows);
        }
        if (!$write) {
            $io->note('Dry run only. Re-run with --write to persist competition and cache changes.');
        }

        return Command::SUCCESS;
    }

    private function needsGeocoding(Competition $competition): bool
    {
        return $competition->getCountryName() === null
            || $competition->getCountryCode() === null
            || $competition->getCityName() === null
            || $this->isSuspiciousGeoValue($competition->getRegionName())
            || $this->isSuspiciousGeoValue($competition->getDepartmentName());
    }

    private function isSuspiciousGeoValue(?string $value): bool
    {
        return $value !== null && preg_match('/[<>]|<br|[0-9]{5}|,/', $value) === 1;
    }

    private function normalizedRawLocation(?string $locationLabel): ?string
    {
        if ($locationLabel === null || trim($locationLabel) === '') {
            return null;
        }

        return trim((string) preg_replace('/\s+/', ' ', html_entity_decode($locationLabel, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    /**
     * @param array{countryName: ?string, countryCode: ?string, regionName: ?string, departmentName: ?string, cityName: ?string, latitude: ?float, longitude: ?float} $geo
     */
    private function isResolved(array $geo): bool
    {
        return $geo['countryName'] !== null
            && $geo['countryCode'] !== null
            && ($geo['cityName'] !== null || $geo['regionName'] !== null);
    }

    /**
     * @param array{countryName: ?string, countryCode: ?string, regionName: ?string, departmentName: ?string, cityName: ?string, latitude: ?float, longitude: ?float} $geo
     */
    private function confidence(array $geo): float
    {
        if ($geo['cityName'] !== null && $geo['regionName'] !== null && $geo['departmentName'] !== null) {
            return 0.85;
        }
        if ($geo['cityName'] !== null && $geo['regionName'] !== null) {
            return 0.7;
        }

        return 0.5;
    }

    /**
     * @param array{countryName: ?string, countryCode: ?string, regionName: ?string, departmentName: ?string, cityName: ?string, latitude: ?float, longitude: ?float} $geo
     */
    private function hasGeoChange(Competition $competition, array $geo): bool
    {
        return $competition->getCountryName() !== $geo['countryName']
            || $competition->getCountryCode() !== $geo['countryCode']
            || $competition->getRegionName() !== $geo['regionName']
            || $competition->getDepartmentName() !== $geo['departmentName']
            || $competition->getCityName() !== $geo['cityName']
            || $competition->getLatitude() !== $geo['latitude']
            || $competition->getLongitude() !== $geo['longitude'];
    }
}
