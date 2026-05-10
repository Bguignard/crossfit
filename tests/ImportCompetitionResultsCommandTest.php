<?php

namespace App\Tests;

use App\Command\ImportCompetitionResultsCommand;
use App\Entity\Competition\Athlete;
use App\Entity\Competition\Competition;
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
        self::assertSame('430 reps', $result->getScore()->getDisplayValue());
        self::assertSame(430.0, $result->getScore()->getNumericValue());
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
