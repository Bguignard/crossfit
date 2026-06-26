<?php

namespace App\Tests;

use App\Command\EnrichWorkoutsCommand;
use App\Entity\Workout\Enum\ImplementEnum;
use App\Entity\Workout\Workout;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group integration
 */
class WorkoutEnrichmentCommandTest extends AbstractIntegrationTest
{
    public function testDryRunReportsMatchesWithoutChangingWorkout(): void
    {
        $workout = $this->workoutByName('JT');
        self::assertCount(0, $workout->getMovements());
        self::assertCount(0, $workout->getImplements());

        $tester = new CommandTester($this->getService(EnrichWorkoutsCommand::class));
        $exitCode = $tester->execute(['--name' => 'JT']);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Workout enrichment dry-run', $tester->getDisplay());
        self::assertStringContainsString('Handstand Push Up', $tester->getDisplay());
        self::assertStringContainsString('Dip', $tester->getDisplay());
        self::assertStringContainsString('Push Up', $tester->getDisplay());
        self::assertStringContainsString('rings', $tester->getDisplay());

        $this->getEntityManager()->refresh($workout);
        self::assertCount(0, $workout->getMovements());
        self::assertCount(0, $workout->getImplements());
    }

    public function testApplyPersistsDeterministicMovementAndImplementLinks(): void
    {
        $tester = new CommandTester($this->getService(EnrichWorkoutsCommand::class));
        $exitCode = $tester->execute(['--name' => 'JT', '--apply' => true]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Workout enrichment applied', $tester->getDisplay());

        $this->getEntityManager()->clear();
        $workout = $this->workoutByName('JT');
        $movementNames = array_map(
            static fn ($movement): ?string => $movement->getName(),
            $workout->getMovements()->toArray()
        );
        $implementNames = array_map(
            static fn ($implement): ImplementEnum => $implement->getNameAsEnum(),
            $workout->getImplements()->toArray()
        );

        self::assertContains('Handstand Push Up', $movementNames);
        self::assertContains('Dip', $movementNames);
        self::assertContains('Push Up', $movementNames);
        self::assertContains(ImplementEnum::RINGS, $implementNames);
    }

    public function testApplyIsIdempotent(): void
    {
        $tester = new CommandTester($this->getService(EnrichWorkoutsCommand::class));
        $tester->execute(['--name' => 'JT', '--apply' => true]);
        $this->getEntityManager()->clear();

        $workout = $this->workoutByName('JT');
        $movementCount = $workout->getMovements()->count();
        $implementCount = $workout->getImplements()->count();

        $tester->execute(['--name' => 'JT', '--apply' => true]);
        $this->getEntityManager()->clear();
        $workout = $this->workoutByName('JT');

        self::assertSame($movementCount, $workout->getMovements()->count());
        self::assertSame($implementCount, $workout->getImplements()->count());
    }

    public function testDryRunReportsAmbiguousTermsForReview(): void
    {
        $tester = new CommandTester($this->getService(EnrichWorkoutsCommand::class));
        $exitCode = $tester->execute(['--name' => 'Lumberjack 20']);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('needs_review', $tester->getDisplay());
        self::assertStringContainsString('squat', $tester->getDisplay());
    }

    private function workoutByName(string $name): Workout
    {
        /** @var Workout|null $workout */
        $workout = $this->getRepository(Workout::class)->findOneBy(['name' => $name]);
        self::assertNotNull($workout);

        return $workout;
    }
}
