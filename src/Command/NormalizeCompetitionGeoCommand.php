<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Competition\Competition;
use App\Services\Competition\CompetitionGeoNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:competitions:normalize-geo',
    description: 'Derive structured competition geography from existing imported labels.',
)]
final class NormalizeCompetitionGeoCommand extends Command
{
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
        $this->addOption('write', null, InputOption::VALUE_NONE, 'Persist changes. Without this option, the command only reports what would change.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = max(1, (int) $input->getOption('limit'));
        $sources = array_values(array_filter(array_map('strval', (array) $input->getOption('source'))));
        $write = (bool) $input->getOption('write');

        $queryBuilder = $this->entityManager->getRepository(Competition::class)
            ->createQueryBuilder('competition')
            ->orderBy('competition.updatedAt', 'DESC')
            ->setMaxResults($limit);

        if (count($sources) > 0) {
            $queryBuilder
                ->andWhere('competition.sourceName IN (:sources)')
                ->setParameter('sources', $sources);
        }

        /** @var list<Competition> $competitions */
        $competitions = $queryBuilder->getQuery()->getResult();
        $changed = 0;
        $rows = [];

        foreach ($competitions as $competition) {
            $geo = $this->competitionGeoNormalizer->fromImportRow([
                'locationLabel' => $competition->getLocationLabel(),
                'isOnline' => $competition->isOnline(),
                'countryName' => $competition->getCountryName(),
                'countryCode' => $competition->getCountryCode(),
                'regionName' => $competition->getRegionName(),
                'departmentName' => $competition->getDepartmentName(),
                'cityName' => $competition->getCityName(),
                'latitude' => $competition->getLatitude(),
                'longitude' => $competition->getLongitude(),
            ]);

            if (!$this->hasGeoChange($competition, $geo)) {
                continue;
            }

            ++$changed;
            if (count($rows) < 20) {
                $rows[] = [
                    $competition->getName(),
                    $competition->getLocationLabel() ?? '-',
                    $geo['cityName'] ?? '-',
                    $geo['regionName'] ?? '-',
                    $geo['countryName'] ?? '-',
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

        $io->title('Competition geography normalization');
        $io->definitionList(
            ['scanned' => count($competitions)],
            [$write ? 'updated' : 'would_update' => $changed],
        );
        if (count($rows) > 0) {
            $io->table(['Competition', 'Location', 'City', 'Region', 'Country'], $rows);
        }
        if (!$write) {
            $io->note('Dry run only. Re-run with --write to persist.');
        }

        return Command::SUCCESS;
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
