<?php

namespace App\Tests;

use App\Command\ImportCompetitionResultsCommand;
use App\Entity\Competition\Athlete;
use App\Entity\Competition\Competition;
use App\Entity\Competition\CompetitionDivision;
use App\Entity\Competition\CompetitionEvent;
use App\Entity\Competition\Score;
use App\Entity\Competition\WorkoutResult;
use App\Entity\Workout\Workout;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

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
                    'flow' => 'For time: run, swim, run.',
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
                ],
            ],
            'events' => [
                [
                    'source' => ['externalId' => 'games-2024-event-1-women'],
                    'competitionSourceId' => 'games-2024',
                    'workoutSourceId' => 'games-2024-event-1-women',
                    'name' => 'Event 1',
                    'eventOrder' => 1,
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
                    'score' => ['type' => 'time', 'rawValue' => '10:00', 'displayValue' => '10:00', 'timeInSeconds' => 600],
                ],
                [
                    'source' => ['externalId' => 'games-2024-event-1-athlete-2'],
                    'athleteSourceId' => 'athlete-2',
                    'eventSourceId' => 'games-2024-event-1-women',
                    'rank' => 2,
                    'division' => 'Women',
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
            self::assertSame('games-2024:division:women', $divisions[0]->getExternalId());

            $results = $this->getRepository(WorkoutResult::class)->findBy([], ['rank' => 'ASC']);
            self::assertCount(2, $results);
            self::assertSame((string) $divisions[0]->getId(), (string) $results[0]->getCompetitionDivision()?->getId());
            self::assertSame((string) $divisions[0]->getId(), (string) $results[1]->getCompetitionDivision()?->getId());
            self::assertSame(40, $results[0]->getFieldSize());

            /** @var Athlete|null $athlete */
            $athlete = $this->getRepository(Athlete::class)->findOneBy([
                'sourceName' => 'crossfit_games',
                'externalId' => 'athlete-1',
            ]);
            self::assertNotNull($athlete);
            self::assertSame('https://profilepicsbucket.crossfit.com/athlete-one.jpg', $athlete->getAvatarUrl());
            self::assertSame(1, $athlete->getEliteGamesRank());
            self::assertSame(2024, $athlete->getEliteGamesSeason());
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
}
