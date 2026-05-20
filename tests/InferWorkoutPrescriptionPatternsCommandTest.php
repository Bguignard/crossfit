<?php

namespace App\Tests;

use App\Command\InferWorkoutPrescriptionPatternsCommand;
use App\Command\PromoteObservedWorkoutPrescriptionStandardsCommand;
use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use App\Entity\Workout\Workout;
use App\Entity\Workout\WorkoutOrigin;
use App\Entity\Workout\WorkoutOriginName;
use App\Entity\Workout\WorkoutPrescriptionStandard;
use Symfony\Component\Console\Tester\CommandTester;

final class InferWorkoutPrescriptionPatternsCommandTest extends AbstractIntegrationTest
{
    public function testReportUsesTextInferredMovementsForImportedWorkouts(): void
    {
        $tester = new CommandTester($this->getService(InferWorkoutPrescriptionPatternsCommand::class));
        $exitCode = $tester->execute(['--name' => 'JT', '--limit' => 1]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Workout prescription pattern report', $tester->getDisplay());
        self::assertStringContainsString('Handstand Push Up', $tester->getDisplay());
        self::assertStringContainsString('rings', $tester->getDisplay());
    }

    public function testObservedStandardsPromotionDryRunDoesNotPersist(): void
    {
        $this->persistObservedWorkout();

        $tester = new CommandTester($this->getService(PromoteObservedWorkoutPrescriptionStandardsCommand::class));
        $exitCode = $tester->execute([
            '--name' => 'Observed 24.1',
            '--limit' => 1,
            '--format' => 'json',
        ]);

        self::assertSame(0, $exitCode);
        $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($payload['dryRun']);
        self::assertSame(2, $payload['stats']['promotable']);
        self::assertSame('would_create', $payload['standards'][0]['status']);
        self::assertSame('women', $payload['standards'][0]['division']);
        self::assertSame('dumbbell', $payload['standards'][0]['implementName']);
        self::assertSame('15.00', $payload['standards'][0]['quantity']);

        self::assertCount(0, $this->getRepository(WorkoutPrescriptionStandard::class)->findBy([
            'sourceName' => 'crossfit_games_observed',
        ]));
    }

    public function testObservedStandardsPromotionWritesAndSkipsDuplicates(): void
    {
        $this->persistObservedWorkout();

        $tester = new CommandTester($this->getService(PromoteObservedWorkoutPrescriptionStandardsCommand::class));
        $exitCode = $tester->execute([
            '--name' => 'Observed 24.1',
            '--limit' => 1,
            '--write' => true,
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('created', $tester->getDisplay());

        $tester = new CommandTester($this->getService(PromoteObservedWorkoutPrescriptionStandardsCommand::class));
        $exitCode = $tester->execute([
            '--name' => 'Observed 24.1',
            '--limit' => 1,
            '--write' => true,
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('existing', $tester->getDisplay());
        $standards = $this->getRepository(WorkoutPrescriptionStandard::class)->findBy([
            'sourceName' => 'crossfit_games_observed',
        ]);
        self::assertCount(2, $standards);
    }

    private function persistObservedWorkout(): void
    {
        $workout = (new Workout(
            'Observed 24.1',
            'For time: 21 dumbbell snatches, arm 1. Time cap: 15 minutes ♀ 35-lb (15-kg) dumbbell ♂ 50-lb (22.5-kg) dumbbell',
            1,
            15,
            null,
            new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CROSSFIT_OPEN_WORKOUT), 2024),
        ))
            ->setSourceName('crossfit_games')
            ->setExternalId('observed-24-1');

        $this->getEntityManager()->persist($workout);
        $this->getEntityManager()->flush();
    }
}
