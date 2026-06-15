<?php

namespace App\Command;

use App\Entity\Competition\Competition;
use App\Entity\Competition\WorkoutResult;
use App\Services\PythonWorker\PythonWorkerClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:competitions:crawl-known-results',
    description: 'Crawl results for known Competition Corner and scoring.fit competitions after they end.',
)]
final class CrawlKnownCompetitionResultsCommand extends Command
{
    /**
     * @var list<string>
     */
    private const SUPPORTED_SOURCES = ['competition_corner', 'scoring_fit'];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PythonWorkerClientInterface $pythonWorkerClient,
        private readonly ImportCompetitionResultsCommand $importCommand,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum ended competitions to inspect.', 20)
            ->addOption('min-ended-hours', null, InputOption::VALUE_REQUIRED, 'Minimum hours since competition end before crawling.', 24)
            ->addOption('retry-after-hours', null, InputOption::VALUE_REQUIRED, 'Minimum hours before retrying a failed or empty crawl.', 24)
            ->addOption('force', null, InputOption::VALUE_NONE, 'Crawl even when imported results already exist or a recent attempt was recorded.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only report eligible competitions without calling the Python worker.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTimeImmutable();
        $limit = max(1, (int) $input->getOption('limit'));
        $minEndedHours = max(1, (int) $input->getOption('min-ended-hours'));
        $retryAfterHours = max(1, (int) $input->getOption('retry-after-hours'));
        $force = (bool) $input->getOption('force');
        $dryRun = (bool) $input->getOption('dry-run');
        $endedBefore = $now->sub(new \DateInterval(sprintf('PT%dH', $minEndedHours)));

        $competitions = $this->eligibleCompetitions($endedBefore, $limit);
        $rows = [];
        $processed = 0;
        $errors = 0;

        foreach ($competitions as $competition) {
            $decision = $this->skipReason($competition, $now, $retryAfterHours, $force);
            if ($decision !== null) {
                $rows[] = $this->reportRow($competition, 'skipped', $decision);

                continue;
            }

            if ($dryRun) {
                $rows[] = $this->reportRow($competition, 'eligible', 'dry-run');

                continue;
            }

            ++$processed;
            $attemptedAt = new \DateTimeImmutable();
            $this->markAttempt($competition, $attemptedAt, 'started');
            $this->entityManager->flush();

            try {
                $response = $this->pythonWorkerClient->crawlCompetitionResults($competition);
                $payload = $this->competitionResultsPayload($response);
                $report = $this->importCommand->importPayload($payload);
                $changes = $this->importedChanges($report['summary']);
                $status = $report['hasFailures'] ? 'failed' : ($changes > 0 ? 'imported' : 'empty');
                $details = $report['hasFailures']
                    ? implode('; ', $report['errors'])
                    : sprintf('%d imported changes', $changes);
                if ($report['hasFailures']) {
                    ++$errors;
                }
                $this->markAttempt($competition, $attemptedAt, $status, $details, $changes);
                $rows[] = $this->reportRow($competition, $status, $details);
            } catch (\Throwable $exception) {
                ++$errors;
                $this->markAttempt($competition, $attemptedAt, 'failed', $exception->getMessage());
                $rows[] = $this->reportRow($competition, 'failed', $exception->getMessage());
            }

            $this->entityManager->flush();
        }

        $io->table(['competition', 'source', 'external id', 'status', 'details'], $rows);
        $io->success(sprintf('Inspected %d competitions, processed %d.', count($competitions), $processed));

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * @return list<Competition>
     */
    private function eligibleCompetitions(\DateTimeImmutable $endedBefore, int $limit): array
    {
        /** @var list<Competition> $competitions */
        $competitions = $this->entityManager->getRepository(Competition::class)->createQueryBuilder('competition')
            ->andWhere('competition.sourceName IN (:sources)')
            ->andWhere('competition.endsAt IS NOT NULL')
            ->andWhere('competition.endsAt <= :endedBefore')
            ->setParameter('sources', self::SUPPORTED_SOURCES)
            ->setParameter('endedBefore', $endedBefore)
            ->orderBy('competition.endsAt', 'DESC')
            ->addOrderBy('competition.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $competitions;
    }

    private function skipReason(Competition $competition, \DateTimeImmutable $now, int $retryAfterHours, bool $force): ?string
    {
        if ($force) {
            return null;
        }

        $resultCount = $this->resultCount($competition);
        if ($resultCount > 0) {
            return sprintf('already has %d imported results', $resultCount);
        }

        $lastAttemptAt = $this->lastAttemptAt($competition);
        if ($lastAttemptAt !== null && $lastAttemptAt > $now->sub(new \DateInterval(sprintf('PT%dH', $retryAfterHours)))) {
            return sprintf('recent attempt at %s', $lastAttemptAt->format(\DateTimeInterface::ATOM));
        }

        return null;
    }

    private function resultCount(Competition $competition): int
    {
        return (int) $this->entityManager->getRepository(WorkoutResult::class)->createQueryBuilder('result')
            ->select('COUNT(result.id)')
            ->join('result.event', 'event')
            ->andWhere('event.competition = :competition')
            ->setParameter('competition', $competition)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function lastAttemptAt(Competition $competition): ?\DateTimeImmutable
    {
        $metadata = $competition->getMetadata() ?? [];
        $crawl = $metadata['postEventResultCrawl'] ?? null;
        if (!is_array($crawl) || !is_string($crawl['lastAttemptAt'] ?? null)) {
            return null;
        }

        try {
            return new \DateTimeImmutable($crawl['lastAttemptAt']);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $response
     *
     * @return array<string, mixed>
     */
    private function competitionResultsPayload(array $response): array
    {
        $payload = $response['competition_results'] ?? $response;
        if (!is_array($payload)) {
            throw new \InvalidArgumentException('Python worker response must include a competition_results object.');
        }

        return $payload;
    }

    /**
     * @param array<string, array{created: int, updated: int, skipped: int, failed: int}> $summary
     */
    private function importedChanges(array $summary): int
    {
        $changes = 0;
        foreach ($summary as $counts) {
            $changes += $counts['created'] + $counts['updated'];
        }

        return $changes;
    }

    private function markAttempt(
        Competition $competition,
        \DateTimeImmutable $attemptedAt,
        string $status,
        ?string $details = null,
        ?int $importedChanges = null,
    ): void {
        $metadata = $competition->getMetadata() ?? [];
        $metadata['postEventResultCrawl'] = array_filter([
            'lastAttemptAt' => $attemptedAt->format(\DateTimeInterface::ATOM),
            'lastSuccessAt' => in_array($status, ['imported', 'empty'], true) ? (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM) : null,
            'lastStatus' => $status,
            'lastDetails' => $details,
            'lastImportedChanges' => $importedChanges,
        ], static fn (mixed $value): bool => $value !== null);
        $competition->setMetadata($metadata);
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: string, 4: string}
     */
    private function reportRow(Competition $competition, string $status, string $details): array
    {
        return [
            $competition->getName(),
            $competition->getSourceName(),
            $competition->getExternalId(),
            $status,
            $details,
        ];
    }
}
