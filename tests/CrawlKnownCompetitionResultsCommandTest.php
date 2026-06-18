<?php

namespace App\Tests;

use App\Command\CrawlKnownCompetitionResultsCommand;
use App\Command\ImportCompetitionResultsCommand;
use App\Entity\Competition\Competition;
use App\Entity\Competition\WorkoutResult;
use App\Entity\Product\PerformanceAnalysisRequest;
use App\Entity\Product\ProgrammingGenerationRequest;
use App\Entity\Product\ProgrammingSessionDetailRequest;
use App\Services\PythonWorker\PythonWorkerClientInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CrawlKnownCompetitionResultsCommandTest extends AbstractIntegrationTest
{
    public function testEndedKnownCompetitionCanCrawlResultsWithoutLinkedAthleteProfile(): void
    {
        $competition = (new Competition('The Gymnase Contest 26.2', 'competition_corner', 'known-contest-262'))
            ->setSourceUrl('https://competitioncorner.net/events/known-contest-262')
            ->setStartsAt(new \DateTimeImmutable('-4 days'))
            ->setEndsAt(new \DateTimeImmutable('-2 days'));
        $olderCompetition = (new Competition('Old Scoring Event', 'scoring_fit', 'old-scoring-event'))
            ->setStartsAt(new \DateTimeImmutable('-180 days'))
            ->setEndsAt(new \DateTimeImmutable('-178 days'));
        $this->getEntityManager()->persist($competition);
        $this->getEntityManager()->persist($olderCompetition);
        $this->getEntityManager()->flush();

        $worker = new FakeKnownCompetitionResultsWorker($this->payloadFor($competition));
        $command = new CrawlKnownCompetitionResultsCommand(
            $this->getEntityManager(),
            $worker,
            $this->importCommand(),
        );
        $tester = new CommandTester($command);

        self::assertSame(Command::SUCCESS, $tester->execute(['--limit' => 1]));
        self::assertSame(1, $worker->calls);
        self::assertSame('known-contest-262', $worker->requestedExternalIds[0]);
        $this->getEntityManager()->clear();

        /** @var list<WorkoutResult> $results */
        $results = $this->getRepository(WorkoutResult::class)->findAll();
        self::assertCount(1, $results);
        self::assertSame('Event 1', $results[0]->getEvent()->getName());
        self::assertSame('Athlete One', $results[0]->getAthlete()->getDisplayName());
        self::assertSame('9:30', $results[0]->getScore()->getDisplayValue());

        /** @var Competition|null $storedCompetition */
        $storedCompetition = $this->getRepository(Competition::class)->findOneBy([
            'sourceName' => 'competition_corner',
            'externalId' => 'known-contest-262',
        ]);
        self::assertNotNull($storedCompetition);
        self::assertSame('imported', $storedCompetition->getMetadata()['postEventResultCrawl']['lastStatus'] ?? null);
        self::assertStringContainsString(
            'crawl requested 1, indexed 1, profiles 1, results 1',
            $storedCompetition->getMetadata()['postEventResultCrawl']['lastDetails'] ?? '',
        );
        self::assertSame(1, $storedCompetition->getMetadata()['postEventResultCrawl']['lastCrawlSummary']['requested'] ?? null);

        $tester = new CommandTester(new CrawlKnownCompetitionResultsCommand(
            $this->getEntityManager(),
            $worker,
            $this->importCommand(),
        ));

        self::assertSame(Command::SUCCESS, $tester->execute(['--limit' => 1]));
        self::assertSame(1, $worker->calls);
        self::assertStringContainsString('already has 1 imported results', $tester->getDisplay());
    }

    public function testRetryRecentIgnoresRecentAttemptButStillSkipsExistingResults(): void
    {
        $recentEmptyCompetition = (new Competition('Recent Empty Event', 'competition_corner', 'recent-empty-event'))
            ->setSourceUrl('https://competitioncorner.net/events/recent-empty-event')
            ->setStartsAt(new \DateTimeImmutable('-3 days'))
            ->setEndsAt(new \DateTimeImmutable('-2 days'))
            ->setMetadata([
                'postEventResultCrawl' => [
                    'lastAttemptAt' => (new \DateTimeImmutable('-1 hour'))->format(\DateTimeInterface::ATOM),
                    'lastStatus' => 'empty',
                ],
            ]);
        $competitionWithResults = (new Competition('Already Imported Event', 'competition_corner', 'already-imported-event'))
            ->setSourceUrl('https://competitioncorner.net/events/already-imported-event')
            ->setStartsAt(new \DateTimeImmutable('-4 days'))
            ->setEndsAt(new \DateTimeImmutable('-2 days'));
        $this->getEntityManager()->persist($recentEmptyCompetition);
        $this->getEntityManager()->persist($competitionWithResults);
        $this->getEntityManager()->flush();

        $worker = new FakeKnownCompetitionResultsWorker($this->payloadFor($competitionWithResults));
        $tester = new CommandTester(new CrawlKnownCompetitionResultsCommand(
            $this->getEntityManager(),
            $worker,
            $this->importCommand(),
        ));

        self::assertSame(Command::SUCCESS, $tester->execute(['--limit' => 2]));
        self::assertSame(1, $worker->calls);
        self::assertSame('already-imported-event', $worker->requestedExternalIds[0]);
        self::assertStringContainsString('recent attempt at', $tester->getDisplay());

        $worker = new FakeKnownCompetitionResultsWorker($this->payloadFor($recentEmptyCompetition));
        $tester = new CommandTester(new CrawlKnownCompetitionResultsCommand(
            $this->getEntityManager(),
            $worker,
            $this->importCommand(),
        ));

        self::assertSame(Command::SUCCESS, $tester->execute(['--limit' => 2, '--retry-recent' => true]));
        self::assertSame(1, $worker->calls);
        self::assertSame('recent-empty-event', $worker->requestedExternalIds[0]);
        self::assertStringContainsString('already has 1 imported results', $tester->getDisplay());
    }

    private function importCommand(): ImportCompetitionResultsCommand
    {
        $command = $this->getService(ImportCompetitionResultsCommand::class);
        self::assertInstanceOf(ImportCompetitionResultsCommand::class, $command);

        return $command;
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadFor(Competition $competition): array
    {
        return [
            'crawl_summary' => [
                'requested' => 1,
                'indexed_competitions' => 1,
                'skipped_existing' => [],
                'missing_or_unavailable' => [],
                'discovered_profiles' => 1,
                'imported_results' => 1,
                'competitions' => [
                    [
                        'event_id' => $competition->getExternalId(),
                        'competition_name' => $competition->getName(),
                        'profiles' => 1,
                        'participations' => 1,
                        'results' => 1,
                    ],
                ],
            ],
            'competition_results' => [
                'contractVersion' => 'competition-results.v1',
                'source' => ['name' => 'competition_corner'],
                'athletes' => [
                    [
                        'source' => ['externalId' => 'known-athlete-1'],
                        'displayName' => 'Athlete One',
                    ],
                ],
                'competitions' => [
                    [
                        'source' => [
                            'externalId' => $competition->getExternalId(),
                            'url' => $competition->getSourceUrl(),
                        ],
                        'name' => $competition->getName(),
                        'status' => 'past',
                        'startsAt' => $competition->getStartsAt()?->format(\DateTimeInterface::ATOM),
                        'endsAt' => $competition->getEndsAt()?->format(\DateTimeInterface::ATOM),
                    ],
                ],
                'events' => [
                    [
                        'source' => ['externalId' => 'known-event-1'],
                        'competitionSourceId' => $competition->getExternalId(),
                        'name' => 'Event 1',
                        'eventOrder' => 1,
                    ],
                ],
                'results' => [
                    [
                        'source' => ['externalId' => 'known-event-1-known-athlete-1'],
                        'athleteSourceId' => 'known-athlete-1',
                        'eventSourceId' => 'known-event-1',
                        'rank' => 1,
                        'fieldSize' => 42,
                        'division' => 'Men',
                        'score' => [
                            'type' => 'time',
                            'rawValue' => '9:30',
                            'displayValue' => '9:30',
                            'timeInSeconds' => 570,
                        ],
                    ],
                ],
            ],
        ];
    }
}

final class FakeKnownCompetitionResultsWorker implements PythonWorkerClientInterface
{
    public int $calls = 0;

    /**
     * @var list<string>
     */
    public array $requestedExternalIds = [];

    /**
     * @param array<string, mixed> $response
     */
    public function __construct(private readonly array $response)
    {
    }

    public function submitPerformanceAnalysis(PerformanceAnalysisRequest $request): array
    {
        throw new \LogicException('Performance analysis is not used in this test.');
    }

    public function submitProgrammingGeneration(ProgrammingGenerationRequest $request): array
    {
        throw new \LogicException('Programming generation is not used in this test.');
    }

    public function submitProgrammingSessionDetails(ProgrammingSessionDetailRequest $request): array
    {
        throw new \LogicException('Programming session details are not used in this test.');
    }

    public function crawlCompetitionResults(Competition $competition): array
    {
        ++$this->calls;
        $this->requestedExternalIds[] = $competition->getExternalId();

        return $this->response;
    }
}
