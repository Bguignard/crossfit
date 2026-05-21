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

    public function testObservedStandardsPromotionHidesDuplicateRowsUnlessRequested(): void
    {
        $this->persistObservedWorkout();
        $this->persistObservedWorkout('Observed 24.1 Repeat', 'observed-24-1-repeat');

        $tester = new CommandTester($this->getService(PromoteObservedWorkoutPrescriptionStandardsCommand::class));
        $exitCode = $tester->execute([
            '--name' => 'Observed 24.1',
            '--limit' => 2,
            '--format' => 'json',
        ]);

        self::assertSame(0, $exitCode);
        $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(4, $payload['stats']['promotable']);
        self::assertSame(2, $payload['stats']['duplicates']);
        self::assertSame(['would_create', 'would_create'], array_column($payload['standards'], 'status'));

        $tester = new CommandTester($this->getService(PromoteObservedWorkoutPrescriptionStandardsCommand::class));
        $exitCode = $tester->execute([
            '--name' => 'Observed 24.1',
            '--limit' => 2,
            '--format' => 'json',
            '--show-duplicates' => true,
        ]);

        self::assertSame(0, $exitCode);
        $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(['would_create', 'would_create', 'duplicate', 'duplicate'], array_column($payload['standards'], 'status'));
    }

    public function testObservedStandardsPromotionSkipsConversionWhenMovementHintsAreIncomplete(): void
    {
        $this->persistObservedWorkout(
            'Observed box step-ups',
            'observed-box-step-ups',
            '4 rounds for max reps of: 1 minute of snatches, 1 minute of rowing for calories, 1 minute of dumbbell box step-ups, 1 minute of rest. ♀ 85-lb (38 kg) barbell, 35-lb (15 kg) dumbbells, 20-inch box ♂ 135-lb (61kg) barbell, 50-lb (22.5 kg) dumbbells, 20-inch box',
        );

        $tester = new CommandTester($this->getService(PromoteObservedWorkoutPrescriptionStandardsCommand::class));
        $exitCode = $tester->execute([
            '--name' => 'Observed box step-ups',
            '--limit' => 1,
            '--format' => 'json',
        ]);

        self::assertSame(0, $exitCode);
        $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(1, $payload['stats']['promotable']);
        self::assertSame(3, $payload['stats']['skipped']);
        self::assertSame(['would_create'], array_column($payload['standards'], 'status'));
        self::assertSame('barbell', $payload['standards'][0]['implementName']);
        self::assertSame('Snatch', $payload['standards'][0]['movementName']);
    }

    public function testObservedStandardsPromotionExpandsLoadLadderConversions(): void
    {
        $this->persistObservedWorkout(
            'Observed deadlift ladder',
            'observed-deadlift-ladder',
            'For time: 3 rounds of 50 double-unders and 10 deadlifts, weight 3 (heaviest). Time cap: 12 minutes ♀ 155, 185, 225 lb (70, 83, 102 kg) ♂ 225, 275, 315 lb (102, 125, 143 kg)',
        );

        $tester = new CommandTester($this->getService(PromoteObservedWorkoutPrescriptionStandardsCommand::class));
        $exitCode = $tester->execute([
            '--name' => 'Observed deadlift ladder',
            '--limit' => 1,
            '--format' => 'json',
        ]);

        self::assertSame(0, $exitCode);
        $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(6, $payload['stats']['promotable']);
        self::assertSame(['70.00', '83.00', '102.00', '102.00', '125.00', '143.00'], array_column($payload['standards'], 'quantity'));
        self::assertSame(['women', 'women', 'women', 'men', 'men', 'men'], array_column($payload['standards'], 'division'));
        self::assertSame(['Deadlift'], array_values(array_unique(array_column($payload['standards'], 'movementName'))));
        self::assertSame(['weight_3'], array_values(array_unique(array_column($payload['standards'], 'contextLabel'))));
    }

    private function persistObservedWorkout(
        string $name = 'Observed 24.1',
        string $externalId = 'observed-24-1',
        string $flow = 'For time: 21 dumbbell snatches, arm 1. Time cap: 15 minutes ♀ 35-lb (15-kg) dumbbell ♂ 50-lb (22.5-kg) dumbbell',
    ): void {
        $workout = (new Workout(
            $name,
            $flow,
            1,
            15,
            null,
            new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CROSSFIT_OPEN_WORKOUT), 2024),
        ))
            ->setSourceName('crossfit_games')
            ->setExternalId($externalId);

        $this->getEntityManager()->persist($workout);
        $this->getEntityManager()->flush();
    }
}
