<?php

namespace App\Command;

use App\Entity\Competition\Competition;
use App\Services\Competition\CompetitionLogoFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:competitions:backfill-logos',
    description: 'Backfill missing Competition Corner and Scoring.fit competition logos.',
)]
final class BackfillCompetitionLogosCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CompetitionLogoFetcher $logoFetcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum competitions to inspect.', '50')
            ->addOption('source', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Limit to a source name. Defaults to competition_corner and scoring_fit.')
            ->addOption('external-id', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Only inspect one competition external id. Can be passed multiple times.')
            ->addOption('before-external-id', null, InputOption::VALUE_REQUIRED, 'Only inspect competitions with an external id lower than this value. Useful to page through missing logos.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Refresh logos even when logo_url is already present.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Fetch logos and print what would change without writing to the database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = max(1, (int) $input->getOption('limit'));
        $sources = array_values(array_filter(array_map('strval', (array) $input->getOption('source'))));
        $externalIds = array_values(array_filter(array_map('strval', (array) $input->getOption('external-id'))));
        $beforeExternalId = trim((string) ($input->getOption('before-external-id') ?? ''));
        $force = (bool) $input->getOption('force');
        $dryRun = (bool) $input->getOption('dry-run');

        if ($sources === []) {
            $sources = ['competition_corner', 'scoring_fit'];
        }

        $inspected = 0;
        $updated = 0;
        $missing = 0;
        $failed = 0;
        $pendingFlushes = 0;
        $lastExternalId = null;

        foreach ($this->findCompetitions($limit, $sources, $externalIds, $beforeExternalId, $force) as $competition) {
            ++$inspected;
            $lastExternalId = $competition->getExternalId();

            try {
                $logoUrl = $this->logoFetcher->fetch($competition);
            } catch (\Throwable $exception) {
                ++$failed;
                $io->warning(sprintf('%s (%s/%s): %s', $competition->getName(), $competition->getSourceName(), $competition->getExternalId(), $exception->getMessage()));
                continue;
            }

            if ($logoUrl === null) {
                ++$missing;
                $io->writeln(sprintf('Missing %s (%s/%s)', $competition->getName(), $competition->getSourceName(), $competition->getExternalId()));
                continue;
            }

            $io->writeln(sprintf('Logo %s (%s/%s): %s', $competition->getName(), $competition->getSourceName(), $competition->getExternalId(), $logoUrl));
            if (!$dryRun) {
                $competition->setLogoUrl($logoUrl);
                ++$updated;
                ++$pendingFlushes;

                if ($pendingFlushes >= 20) {
                    $this->entityManager->flush();
                    $pendingFlushes = 0;
                }
            }
        }

        if ($pendingFlushes > 0 && !$dryRun) {
            $this->entityManager->flush();
        }

        $io->table(
            ['inspected', 'updated', 'missing', 'failed'],
            [[$inspected, $updated, $missing, $failed]],
        );

        if ($lastExternalId !== null && $externalIds === []) {
            $io->writeln(sprintf('Next cursor: --before-external-id=%s', $lastExternalId));
        }

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * @param list<string> $sources
     * @param list<string> $externalIds
     *
     * @return list<Competition>
     */
    private function findCompetitions(int $limit, array $sources, array $externalIds, string $beforeExternalId, bool $force): array
    {
        $queryBuilder = $this->entityManager->getRepository(Competition::class)->createQueryBuilder('competition')
            ->andWhere('competition.sourceName IN (:sources)')
            ->setParameter('sources', $sources)
            ->orderBy('competition.sourceName', 'ASC')
            ->addOrderBy('competition.externalId', 'DESC')
            ->setMaxResults($limit);

        if ($externalIds !== []) {
            $queryBuilder
                ->andWhere('competition.externalId IN (:externalIds)')
                ->setParameter('externalIds', $externalIds);
        } else {
            if ($beforeExternalId !== '') {
                $queryBuilder
                    ->andWhere('competition.externalId < :beforeExternalId')
                    ->setParameter('beforeExternalId', $beforeExternalId);
            }

            if (!$force) {
                $queryBuilder->andWhere('competition.logoUrl IS NULL');
            }
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
