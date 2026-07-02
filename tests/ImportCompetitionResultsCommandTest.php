<?php

namespace App\Tests;

use App\Command\ImportCompetitionResultsCommand;
use App\Entity\Competition\Athlete;
use App\Entity\Competition\Competition;
use App\Entity\Competition\CompetitionDivision;
use App\Entity\Competition\CompetitionEvent;
use App\Entity\Competition\CompetitionParticipation;
use App\Entity\Competition\Score;
use App\Entity\Competition\WorkoutResult;
use App\Entity\Workout\Workout;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group integration
 */
class ImportCompetitionResultsCommandTest extends AbstractIntegrationTest
{
    public function testImportExamplePayloadIsIdempotent(): void
    {
        $command = $this->getService(ImportCompetitionResultsCommand::class);
        self::assertInstanceOf(ImportCompetitionResultsCommand::class, $command);

        $tester = new CommandTester($command);
        $fixturePath = static::getContainer()->getParameter('kernel.project_dir').'/examples/import/competition-results.v1.json';
        $initialWorkoutCount = count($this->getRepository(Workout::class)->findAll());

        self::assertSame(Command::SUCCESS, $tester->execute(['file' => $fixturePath]));
        $this->getEntityManager()->clear();

        self::assertSame(Command::SUCCESS, $tester->execute(['file' => $fixturePath]));
        $this->getEntityManager()->clear();

        self::assertCount($initialWorkoutCount + 1, $this->getRepository(Workout::class)->findAll());
        self::assertCount(1, $this->getRepository(Athlete::class)->findAll());
        self::assertCount(1, $this->getRepository(Competition::class)->findAll());
        self::assertCount(1, $this->getRepository(CompetitionParticipation::class)->findAll());
        self::assertCount(1, $this->getRepository(CompetitionDivision::class)->findAll());
        self::assertCount(1, $this->getRepository(CompetitionEvent::class)->findAll());
        self::assertCount(1, $this->getRepository(WorkoutResult::class)->findAll());
        self::assertCount(1, $this->getRepository(Score::class)->findAll());

        /** @var Workout|null $workout */
        $workout = $this->getRepository(Workout::class)->findOneBy([
            'sourceName' => 'crossfit_games',
            'externalId' => 'cf-games-2016-open-16-2',
        ]);
        /** @var WorkoutResult|null $result */
        $result = $this->getRepository(WorkoutResult::class)->findOneBy([
            'sourceName' => 'crossfit_games',
            'externalId' => 'crossfit-open-2016-event-16-2-athlete-12348',
        ]);

        self::assertNotNull($workout);
        self::assertSame('Open 16.2', $workout->getName());
        self::assertStringContainsString('25 toes-to-bars', $workout->getFlow());
        self::assertNotNull($result);
        self::assertSame('Women', $result->getDivision());
        self::assertSame('Women', $result->getCompetitionDivision()?->getName());
        self::assertSame('430 reps', $result->getScore()->getDisplayValue());
        self::assertSame(430.0, $result->getScore()->getNumericValue());
    }

    public function testImportReusesStructuredCompetitionDivision(): void
    {
        $command = $this->getService(ImportCompetitionResultsCommand::class);
        self::assertInstanceOf(ImportCompetitionResultsCommand::class, $command);

        $file = tempnam(sys_get_temp_dir(), 'competition-import-');
        self::assertIsString($file);
        file_put_contents($file, json_encode([
            'contractVersion' => 'competition-results.v1',
            'source' => ['name' => 'crossfit_games'],
            'workouts' => [
                [
                    'source' => ['externalId' => 'games-2024-event-1-women'],
                    'name' => 'Event 1',
                    'normalizedName' => 'event 1',
                    'flow' => 'For time: run, swim, run.',
                    'normalizedFlow' => 'for time run swim run',
                    'canonicalFingerprint' => 'analyser-event-1-fingerprint',
                    'originName' => 'CrossFit Games',
                    'originYear' => 2024,
                ],
            ],
            'athletes' => [
                [
                    'source' => ['externalId' => 'athlete-1'],
                    'displayName' => 'Athlete One',
                    'avatarUrl' => 'https://profilepicsbucket.crossfit.com/athlete-one.jpg',
                    'eliteGamesRank' => 1,
                    'eliteGamesSeason' => 2024,
                ],
                ['source' => ['externalId' => 'athlete-2'], 'displayName' => 'Athlete Two'],
            ],
            'competitions' => [
                [
                    'source' => ['externalId' => 'games-2024'],
                    'name' => 'CrossFit Games',
                    'season' => 2024,
                    'logoUrl' => 'https://example.test/crossfit-games.png',
                    'status' => 'past',
                    'startsAt' => '2024-08-08T12:00:00+00:00',
                    'endsAt' => '2024-08-11T20:00:00+00:00',
                    'registrationUrl' => 'https://example.test/register',
                    'locationLabel' => 'Fort Worth, TX',
                    'countryName' => 'United States',
                    'countryCode' => 'US',
                    'regionName' => 'Texas',
                    'departmentName' => 'Tarrant County',
                    'cityName' => 'Fort Worth',
                    'latitude' => 32.7555,
                    'longitude' => -97.3308,
                    'isOnline' => false,
                    'competitionType' => 'functional_fitness',
                    'participationType' => 'individual',
                    'coverImageUrl' => 'https://example.test/cover.jpg',
                    'priceLabel' => '$25',
                    'metadata' => ['sourceCategory' => 'Games'],
                    'lastDiscoveredAt' => '2026-05-17T10:30:00+00:00',
                ],
            ],
            'participations' => [
                [
                    'source' => ['externalId' => 'games-2024:athlete-1'],
                    'athleteSourceId' => 'athlete-1',
                    'competitionSourceId' => 'games-2024',
                    'rank' => '7',
                    'division' => 'Women',
                    'divisionSourceId' => 'women',
                    'format' => 'Individual',
                    'formatSlug' => 'individual',
                ],
            ],
            'events' => [
                [
                    'source' => ['externalId' => 'games-2024-event-1-women'],
                    'competitionSourceId' => 'games-2024',
                    'workoutSourceId' => 'games-2024-event-1-women',
                    'name' => 'Event 1',
                    'eventOrder' => 1,
                    'provenances' => [
                        [
                            'sourceWorkoutId' => 'games-2024-event-1',
                            'sourceWorkoutUrl' => 'https://example.test/workouts/event-1',
                            'division' => 'Women',
                            'divisionSourceId' => 'women',
                            'format' => 'Individual',
                            'formatSlug' => 'individual',
                        ],
                    ],
                ],
            ],
            'results' => [
                [
                    'source' => ['externalId' => 'games-2024-event-1-athlete-1'],
                    'athleteSourceId' => 'athlete-1',
                    'eventSourceId' => 'games-2024-event-1-women',
                    'rank' => 1,
                    'fieldSize' => 40,
                    'division' => 'Women',
                    'divisionSourceId' => 'women',
                    'competitionRank' => '7',
                    'competitionFormat' => 'Individual',
                    'competitionFormatSlug' => 'individual',
                    'score' => ['type' => 'time', 'rawValue' => '10:00', 'displayValue' => '10:00', 'timeInSeconds' => 600],
                ],
                [
                    'source' => ['externalId' => 'games-2024-event-1-athlete-2'],
                    'athleteSourceId' => 'athlete-2',
                    'eventSourceId' => 'games-2024-event-1-women',
                    'rank' => 2,
                    'division' => 'Women',
                    'divisionSourceId' => 'women',
                    'score' => ['type' => 'time', 'rawValue' => '10:30', 'displayValue' => '10:30', 'timeInSeconds' => 630],
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        try {
            $tester = new CommandTester($command);

            self::assertSame(Command::SUCCESS, $tester->execute(['file' => $file]));
            $this->getEntityManager()->clear();

            /** @var list<CompetitionDivision> $divisions */
            $divisions = $this->getRepository(CompetitionDivision::class)->findAll();
            self::assertCount(1, $divisions);
            self::assertSame('Women', $divisions[0]->getName());
            self::assertSame('women', $divisions[0]->getExternalId());

            $results = $this->getRepository(WorkoutResult::class)->findBy([], ['rank' => 'ASC']);
            self::assertCount(2, $results);
            self::assertSame((string) $divisions[0]->getId(), (string) $results[0]->getCompetitionDivision()?->getId());
            self::assertSame((string) $divisions[0]->getId(), (string) $results[1]->getCompetitionDivision()?->getId());
            self::assertSame(40, $results[0]->getFieldSize());
            self::assertSame('women', $results[0]->getDivisionSourceId());
            self::assertSame('7', $results[0]->getCompetitionRank());
            self::assertSame('Individual', $results[0]->getCompetitionFormat());
            self::assertSame('individual', $results[0]->getCompetitionFormatSlug());

            /** @var list<CompetitionParticipation> $participations */
            $participations = $this->getRepository(CompetitionParticipation::class)->findAll();
            self::assertCount(2, $participations);
            $byExternalId = [];
            foreach ($participations as $participation) {
                $byExternalId[$participation->getExternalId()] = $participation;
            }
            self::assertArrayHasKey('games-2024:athlete-1', $byExternalId);
            self::assertArrayHasKey('games-2024:athlete-2', $byExternalId);
            self::assertSame('7', $byExternalId['games-2024:athlete-1']->getRank());
            self::assertSame('Women', $byExternalId['games-2024:athlete-1']->getDivision());
            self::assertSame('women', $byExternalId['games-2024:athlete-1']->getDivisionSourceId());
            self::assertSame('Individual', $byExternalId['games-2024:athlete-1']->getFormat());
            self::assertSame('individual', $byExternalId['games-2024:athlete-1']->getFormatSlug());

            /** @var Athlete|null $athlete */
            $athlete = $this->getRepository(Athlete::class)->findOneBy([
                'sourceName' => 'crossfit_games',
                'externalId' => 'athlete-1',
            ]);
            self::assertNotNull($athlete);
            self::assertSame('https://profilepicsbucket.crossfit.com/athlete-one.jpg', $athlete->getAvatarUrl());
            self::assertSame(1, $athlete->getEliteGamesRank());
            self::assertSame(2024, $athlete->getEliteGamesSeason());

            /** @var Competition|null $competition */
            $competition = $this->getRepository(Competition::class)->findOneBy([
                'sourceName' => 'crossfit_games',
                'externalId' => 'games-2024',
            ]);
            self::assertNotNull($competition);
            self::assertSame('https://example.test/crossfit-games.png', $competition->getLogoUrl());
            self::assertSame('past', $competition->getStatus());
            self::assertSame('2024-08-08T12:00:00+00:00', $competition->getStartsAt()?->format(DATE_ATOM));
            self::assertSame('2024-08-11T20:00:00+00:00', $competition->getEndsAt()?->format(DATE_ATOM));
            self::assertSame('https://example.test/register', $competition->getRegistrationUrl());
            self::assertSame('Fort Worth, TX', $competition->getLocationLabel());
            self::assertSame('United States', $competition->getCountryName());
            self::assertSame('US', $competition->getCountryCode());
            self::assertSame('Texas', $competition->getRegionName());
            self::assertSame('Tarrant County', $competition->getDepartmentName());
            self::assertSame('Fort Worth', $competition->getCityName());
            self::assertSame(32.7555, $competition->getLatitude());
            self::assertSame(-97.3308, $competition->getLongitude());
            self::assertFalse($competition->isOnline());
            self::assertSame('functional_fitness', $competition->getCompetitionType());
            self::assertSame('individual', $competition->getParticipationType());
            self::assertSame('https://example.test/cover.jpg', $competition->getCoverImageUrl());
            self::assertSame('$25', $competition->getPriceLabel());
            self::assertSame(['sourceCategory' => 'Games'], $competition->getMetadata());
            self::assertSame('2026-05-17T10:30:00+00:00', $competition->getLastDiscoveredAt()?->format(DATE_ATOM));

            /** @var Workout|null $workout */
            $workout = $this->getRepository(Workout::class)->findOneBy([
                'sourceName' => 'crossfit_games',
                'externalId' => 'games-2024-event-1-women',
            ]);
            self::assertNotNull($workout);
            self::assertSame('event 1', $workout->getNormalizedName());
            self::assertSame('for time run swim run', $workout->getNormalizedFlow());
            self::assertSame('analyser-event-1-fingerprint', $workout->getCanonicalFingerprint());

            /** @var CompetitionEvent|null $event */
            $event = $this->getRepository(CompetitionEvent::class)->findOneBy([
                'sourceName' => 'crossfit_games',
                'externalId' => 'games-2024-event-1-women',
            ]);
            self::assertNotNull($event);
            self::assertEquals([
                [
                    'sourceWorkoutId' => 'games-2024-event-1',
                    'sourceWorkoutUrl' => 'https://example.test/workouts/event-1',
                    'division' => 'Women',
                    'divisionSourceId' => 'women',
                    'format' => 'Individual',
                    'formatSlug' => 'individual',
                ],
            ], $event->getProvenances());
        } finally {
            @unlink($file);
        }
    }

    public function testImportPersistsHyroxPerformanceBreakdownSplits(): void
    {
        $command = $this->getService(ImportCompetitionResultsCommand::class);
        self::assertInstanceOf(ImportCompetitionResultsCommand::class, $command);

        $file = tempnam(sys_get_temp_dir(), 'competition-import-');
        self::assertIsString($file);
        file_put_contents($file, json_encode([
            'contractVersion' => 'competition-results.v1',
            'source' => ['name' => 'hyrox'],
            'athletes' => [
                ['source' => ['externalId' => 'hyrox-athlete-1'], 'displayName' => 'HYROX Athlete'],
            ],
            'competitions' => [
                [
                    'source' => ['externalId' => 'hyrox-paris-2026'],
                    'name' => 'HYROX Paris 2026',
                    'competitionType' => 'hyrox',
                ],
            ],
            'events' => [
                [
                    'source' => ['externalId' => 'hyrox-paris-2026-pro'],
                    'competitionSourceId' => 'hyrox-paris-2026',
                    'name' => 'HYROX Pro',
                    'eventOrder' => 1,
                ],
            ],
            'results' => [
                [
                    'source' => ['externalId' => 'hyrox-paris-2026-athlete-1'],
                    'athleteSourceId' => 'hyrox-athlete-1',
                    'eventSourceId' => 'hyrox-paris-2026-pro',
                    'rank' => 9,
                    'division' => 'Pro Men',
                    'score' => ['type' => 'time', 'rawValue' => '1:02:05', 'displayValue' => '1:02:05', 'timeInSeconds' => 3725],
                    'totalTimeSeconds' => 3725,
                    'performanceDetails' => [
                        'sport' => 'hyrox',
                        'totalTime' => '1:02:05',
                        'totalTimeSeconds' => 3725,
                        'resultSummary' => [
                            'category' => 'total',
                            'displayLabel' => 'Total',
                            'duration' => '1:02:05',
                            'durationSeconds' => 3725,
                            'rank' => 9,
                        ],
                        'segments' => [
                            [
                                'key' => 'run_1',
                                'order' => 1,
                                'kind' => 'run',
                                'category' => 'run',
                                'name' => 'Run 1',
                                'displayLabel' => 'Run 1',
                                'distance_meters' => 1000,
                                'durationSeconds' => 255,
                            ],
                            [
                                'key' => 'skierg',
                                'order' => 2,
                                'kind' => 'station',
                                'category' => 'station',
                                'station_number' => 1,
                                'name' => '1000m SkiErg',
                                'displayLabel' => 'SkiErg',
                                'distance_meters' => 1000,
                                'durationSeconds' => 270,
                                'analysisArea' => 'ergs_engine',
                            ],
                        ],
                        'segmentGroups' => [
                            'runs' => [
                                ['key' => 'run_1', 'displayLabel' => 'Run 1', 'durationSeconds' => 255],
                            ],
                            'stations' => [
                                ['key' => 'skierg', 'displayLabel' => 'SkiErg', 'durationSeconds' => 270],
                            ],
                            'roxzone' => [],
                            'unknown' => [],
                        ],
                        'analysisSummary' => [
                            'areas' => [
                                'running' => ['segmentCount' => 1, 'totalDurationSeconds' => 255],
                                'ergs_engine' => ['segmentCount' => 1, 'totalDurationSeconds' => 270],
                            ],
                        ],
                        'exportQuality' => [
                            'expectedSegmentCount' => 17,
                            'knownSegmentCount' => 2,
                            'missingSegmentCount' => 15,
                            'isComplete' => false,
                            'missingSegmentKeys' => ['run_2'],
                        ],
                        'missingSegments' => [
                            ['key' => 'run_2', 'name' => 'Run 2', 'kind' => 'run', 'order' => 3],
                        ],
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        try {
            $tester = new CommandTester($command);

            self::assertSame(Command::SUCCESS, $tester->execute(['file' => $file]));
            $this->getEntityManager()->clear();

            /** @var WorkoutResult|null $result */
            $result = $this->getRepository(WorkoutResult::class)->findOneBy([
                'sourceName' => 'hyrox',
                'externalId' => 'hyrox-paris-2026-athlete-1',
            ]);

            self::assertNotNull($result);
            $breakdown = $result->getPerformanceBreakdown();
            self::assertIsArray($breakdown);
            self::assertSame('hyrox', $breakdown['sport']);
            self::assertSame(3725, $breakdown['totalTimeSeconds']);
            self::assertSame('Total', $breakdown['resultSummary']['displayLabel']);
            self::assertCount(2, $breakdown['segments']);
            self::assertSame('run_1', $breakdown['segments'][0]['key']);
            self::assertSame('run', $breakdown['segments'][0]['kind']);
            self::assertSame('Run 1', $breakdown['segments'][0]['name']);
            self::assertSame(1000, $breakdown['segments'][0]['distance_meters']);
            self::assertSame('station', $breakdown['segments'][1]['kind']);
            self::assertSame('SkiErg', $breakdown['segments'][1]['displayLabel']);
            self::assertSame(270, $breakdown['segments'][1]['durationSeconds']);
            self::assertSame(['run_1'], array_column($breakdown['segmentGroups']['runs'], 'key'));
            self::assertSame(270, $breakdown['analysisSummary']['areas']['ergs_engine']['totalDurationSeconds']);
            self::assertFalse($breakdown['exportQuality']['isComplete']);
            self::assertSame(['run_2'], $breakdown['exportQuality']['missingSegmentKeys']);
        } finally {
            @unlink($file);
        }
    }

    public function testImportDeduplicatesUnflushedCompetitionParticipations(): void
    {
        $command = $this->getService(ImportCompetitionResultsCommand::class);
        self::assertInstanceOf(ImportCompetitionResultsCommand::class, $command);

        $file = tempnam(sys_get_temp_dir(), 'competition-import-');
        self::assertIsString($file);
        file_put_contents($file, json_encode([
            'contractVersion' => 'competition-results.v1',
            'source' => ['name' => 'competition_corner'],
            'athletes' => [
                ['source' => ['externalId' => '585064'], 'displayName' => 'Duplicate Athlete'],
            ],
            'competitions' => [
                ['source' => ['externalId' => '19804'], 'name' => 'Marseille Throwdown 2026 Online Qualifier'],
            ],
            'participations' => [
                [
                    'source' => ['externalId' => '19804:585064'],
                    'athleteSourceId' => '585064',
                    'competitionSourceId' => '19804',
                    'rank' => '12',
                    'division' => 'Elite Male',
                ],
                [
                    'source' => ['externalId' => '19804:585064'],
                    'athleteSourceId' => '585064',
                    'competitionSourceId' => '19804',
                    'rank' => '11',
                    'division' => 'Elite Male',
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        try {
            $tester = new CommandTester($command);

            self::assertSame(Command::SUCCESS, $tester->execute(['file' => $file]));
            $this->getEntityManager()->clear();

            /** @var list<CompetitionParticipation> $participations */
            $participations = $this->getRepository(CompetitionParticipation::class)->findAll();
            self::assertCount(1, $participations);
            self::assertSame('19804:585064', $participations[0]->getExternalId());
            self::assertSame('11', $participations[0]->getRank());
            self::assertSame('Elite Male', $participations[0]->getDivision());
        } finally {
            @unlink($file);
        }
    }

    public function testInvalidRowsAreReportedWithoutBlockingValidRows(): void
    {
        $command = $this->getService(ImportCompetitionResultsCommand::class);
        self::assertInstanceOf(ImportCompetitionResultsCommand::class, $command);

        $file = tempnam(sys_get_temp_dir(), 'competition-import-');
        self::assertIsString($file);
        file_put_contents($file, json_encode([
            'contractVersion' => 'competition-results.v1',
            'source' => ['name' => 'crossfit_games'],
            'athletes' => [
                [
                    'source' => ['externalId' => 'valid-athlete'],
                    'displayName' => 'Valid Athlete',
                ],
                [
                    'source' => ['externalId' => 'invalid-athlete'],
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        try {
            $tester = new CommandTester($command);

            self::assertSame(Command::FAILURE, $tester->execute(['file' => $file]));
            $this->getEntityManager()->clear();

            self::assertNotNull($this->getRepository(Athlete::class)->findOneBy([
                'sourceName' => 'crossfit_games',
                'externalId' => 'valid-athlete',
            ]));
            self::assertNull($this->getRepository(Athlete::class)->findOneBy([
                'sourceName' => 'crossfit_games',
                'externalId' => 'invalid-athlete',
            ]));
            self::assertStringContainsString('displayName is required', $tester->getDisplay());
        } finally {
            @unlink($file);
        }
    }

    public function testPlaceholderWorkoutFlowsAreSkipped(): void
    {
        $command = $this->getService(ImportCompetitionResultsCommand::class);
        self::assertInstanceOf(ImportCompetitionResultsCommand::class, $command);

        $file = tempnam(sys_get_temp_dir(), 'competition-import-');
        self::assertIsString($file);
        file_put_contents($file, json_encode([
            'contractVersion' => 'competition-results.v1',
            'source' => ['name' => 'competition_corner'],
            'workouts' => [
                [
                    'source' => ['externalId' => 'marseille-2025-workout-1'],
                    'name' => 'Workout 1',
                    'flow' => '*',
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        try {
            $tester = new CommandTester($command);

            self::assertSame(Command::SUCCESS, $tester->execute(['file' => $file]));
            $this->getEntityManager()->clear();

            self::assertNull($this->getRepository(Workout::class)->findOneBy([
                'sourceName' => 'competition_corner',
                'externalId' => 'marseille-2025-workout-1',
            ]));
            self::assertStringContainsString('skipped', $tester->getDisplay());
        } finally {
            @unlink($file);
        }
    }
}
