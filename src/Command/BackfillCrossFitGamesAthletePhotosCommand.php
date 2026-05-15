<?php

namespace App\Command;

use App\Entity\Competition\Athlete;
use App\Services\Competition\CrossFitGamesProfilePhotoFetcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:athletes:backfill-games-photos',
    description: 'Backfill missing CrossFit Games athlete photos directly into Symfony athletes.',
)]
final class BackfillCrossFitGamesAthletePhotosCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CrossFitGamesProfilePhotoFetcher $photoFetcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum athletes to inspect.', '50')
            ->addOption('external-id', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Only inspect one CrossFit Games athlete external id. Can be passed multiple times.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Refresh photos even when avatar_url is already present.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Fetch photos and print what would change without writing to the database.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = max(1, (int) $input->getOption('limit'));
        $externalIds = array_values(array_filter(array_map('strval', (array) $input->getOption('external-id'))));
        $force = (bool) $input->getOption('force');
        $dryRun = (bool) $input->getOption('dry-run');

        $inspected = 0;
        $updated = 0;
        $missing = 0;
        $failed = 0;

        foreach ($this->findAthletes($limit, $externalIds, $force) as $athlete) {
            ++$inspected;

            try {
                $avatarUrl = $this->fetchAvatarUrl($athlete);
            } catch (\Throwable $exception) {
                ++$failed;
                $io->warning(sprintf('%s (%s): %s', $athlete->getDisplayName(), $athlete->getExternalId(), $exception->getMessage()));
                continue;
            }

            if ($avatarUrl === null) {
                ++$missing;
                $io->writeln(sprintf('Missing %s (%s)', $athlete->getDisplayName(), $athlete->getExternalId()));
                continue;
            }

            $io->writeln(sprintf('Photo %s (%s): %s', $athlete->getDisplayName(), $athlete->getExternalId(), $avatarUrl));
            if (!$dryRun) {
                $athlete->setAvatarUrl($avatarUrl);
                ++$updated;
            }
        }

        if ($updated > 0 && !$dryRun) {
            $this->entityManager->flush();
        }

        $io->table(
            ['inspected', 'updated', 'missing', 'failed'],
            [[$inspected, $updated, $missing, $failed]],
        );

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * @param list<string> $externalIds
     *
     * @return list<Athlete>
     */
    private function findAthletes(int $limit, array $externalIds, bool $force): array
    {
        $queryBuilder = $this->entityManager->getRepository(Athlete::class)->createQueryBuilder('athlete')
            ->andWhere('athlete.sourceName = :sourceName')
            ->setParameter('sourceName', 'crossfit_games')
            ->orderBy('athlete.eliteGamesSortScore', 'ASC')
            ->addOrderBy('athlete.displayName', 'ASC')
            ->setMaxResults($limit);

        if ($externalIds !== []) {
            $queryBuilder
                ->andWhere('athlete.externalId IN (:externalIds)')
                ->setParameter('externalIds', $externalIds);
        } elseif (!$force) {
            $queryBuilder->andWhere('athlete.avatarUrl IS NULL');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    private function fetchAvatarUrl(Athlete $athlete): ?string
    {
        $profileUrl = $athlete->getSourceUrl() ?: sprintf('https://games.crossfit.com/athlete/%s', $athlete->getExternalId());
        return $this->photoFetcher->fetch($profileUrl);
    }
}
