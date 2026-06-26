<?php

namespace App\Tests;

use App\DataFixtures\ImplementData;
use App\DataFixtures\MovementData;
use App\DataFixtures\MuscleData;
use App\DataFixtures\WorkoutData;
use App\Entity\Workout\Enum\ImplementEnum;
use App\Entity\Workout\Enum\MeasureUnitEnum;
use App\Entity\Workout\Enum\MovementDifficultyEnum;
use App\Entity\Workout\Enum\MovementTypeEnum;
use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use App\Entity\Workout\Enum\WorkoutTypeEnum;
use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\Muscle;
use App\Entity\Workout\Workout;

/**
 * @group integration
 */
class ProductFixturesTest extends AbstractIntegrationTest
{
    public function testWorkoutFixturesExposeMonolithicWorkoutsWithEnrichmentMetadata(): void
    {
        /** @var Workout $fran */
        $fran = $this->getReference(WorkoutData::WORKOUT_FRAN, Workout::class);

        self::assertSame('Fran', $fran->getName());
        self::assertStringContainsString('21-15-9', $fran->getFlow());
        self::assertStringContainsString('Thrusters', $fran->getFlow());
        self::assertStringContainsString('Pull-ups', $fran->getFlow());
        self::assertSame(10, $fran->getTimeCap());
        self::assertSame(WorkoutTypeEnum::FOR_TIME, $fran->getWorkoutType()?->getNameAsEnum());
        self::assertSame(WorkoutOriginNameEnum::GIRLS_WORKOUT, $fran->getWorkoutOrigin()->getName()->getNameAsEnum());

        self::assertCount(2, $fran->getMovements());
        self::assertCount(2, $fran->getImplements());
        self::assertContainsOnlyInstancesOf(Movement::class, $fran->getMovements());
        self::assertContainsOnlyInstancesOf(Implement::class, $fran->getImplements());
    }

    public function testMovementOntologyCanGuideWorkoutGenerationWithoutStructuredBlocks(): void
    {
        /** @var Movement $thruster */
        $thruster = $this->getReference(MovementData::MOVEMENT_THRUSTER, Movement::class);
        /** @var Implement $barbell */
        $barbell = $this->getReference(ImplementData::IMPLEMENT_BARBELL, Implement::class);

        self::assertSame('Thruster', $thruster->getName());
        self::assertEquals(MovementDifficultyEnum::BEGINNER, $thruster->getDifficulty()->getNameAsEnum());
        self::assertEquals(MovementTypeEnum::WEIGHTLIFTING, $thruster->getMovementType()->getNameAsEnum());
        self::assertNotEmpty($thruster->getMuscles());
        self::assertTrue($thruster->getPossibleImplements()->contains($barbell));

        $executionUnits = $thruster->getMovementExecutionTimeForMeasureUnits();
        self::assertNotEmpty($executionUnits);
        self::assertTrue(
            $executionUnits->exists(
                static fn (int $index, $executionTime): bool => $executionTime->getMeasureUnit() === MeasureUnitEnum::REPETITION
                    && $executionTime->getExecutionTimeInMilliseconds() > 0
            )
        );
        self::assertEquals(ImplementEnum::BARBELL, $barbell->getNameAsEnum());
    }

    public function testHeroWorkoutCatalogContainsLegacyYamlCoverage(): void
    {
        $heroWorkoutNames = [];

        /** @var Workout $workout */
        foreach ($this->getRepository(Workout::class)->findAll() as $workout) {
            if ($workout->getWorkoutOrigin()->getName()->getNameAsEnum() !== WorkoutOriginNameEnum::HERO_WORKOUT) {
                continue;
            }

            $heroWorkoutNames[] = $workout->getName();
        }

        self::assertCount(237, $heroWorkoutNames);

        foreach ([
            'JT',
            'Michael',
            'Jason',
            'Joshie',
            'Tommy V',
            'Griff',
            'Ryan',
            'Erin',
            'Mr. Joshua',
            'DT',
            'Danny',
            'Hansen',
            'Tyler',
            'Lumberjack 20',
            'Nutts',
            'Arnie',
            'The Seven',
            'RJ',
            'Luce',
            'Johnson',
            'Jack',
            'Forrest',
            'Bull',
            'Holbrook',
        ] as $expectedHeroWorkoutName) {
            self::assertContains($expectedHeroWorkoutName, $heroWorkoutNames);
        }
    }

    public function testMovementOntologyKeepsHighSignalMappingsForGenerationPrompts(): void
    {
        /** @var Movement $burpeeRopeClimb */
        $burpeeRopeClimb = $this->getReference(MovementData::MOVEMENT_BURPEE_ROPE_CLIMB, Movement::class);
        /** @var Implement $rope */
        $rope = $this->getReference(ImplementData::IMPLEMENT_ROPE, Implement::class);
        /** @var Implement $pullUpBar */
        $pullUpBar = $this->getReference(ImplementData::IMPLEMENT_PULL_UP_BAR, Implement::class);

        self::assertTrue($burpeeRopeClimb->getPossibleImplements()->contains($rope));
        self::assertFalse($burpeeRopeClimb->getPossibleImplements()->contains($pullUpBar));

        /** @var Movement $broadJump */
        $broadJump = $this->getReference(MovementData::MOVEMENT_BROAD_JUMP, Movement::class);
        /** @var Implement $band */
        $band = $this->getReference(ImplementData::IMPLEMENT_BAND, Implement::class);

        self::assertFalse($broadJump->getPossibleImplements()->contains($band));
        $this->assertMovementHasMuscles($broadJump, [
            MuscleData::MUSCLE_QUADRICEPS,
            MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
            MuscleData::MUSCLE_HAMSTRINGS,
            MuscleData::MUSCLE_CALVES,
        ]);

        /** @var Movement $muscleSnatch */
        $muscleSnatch = $this->getReference(MovementData::MOVEMENT_MUSCLE_SNATCH, Movement::class);

        $this->assertMovementHasMuscles($muscleSnatch, [
            MuscleData::MUSCLE_TRAPEZIUS,
            MuscleData::MUSCLE_DELTOIDS,
            MuscleData::MUSCLE_FOREARMS,
            MuscleData::MUSCLE_SPINAL_ERECTORS,
        ]);

        /** @var Movement $wallFacingStrictHandstandPushUp */
        $wallFacingStrictHandstandPushUp = $this->getReference(MovementData::MOVEMENT_WALL_FACING_STRICT_HANDSTAND_PUSH_UP, Movement::class);

        self::assertSame('Wall Facing Strict Handstand Push Up', $wallFacingStrictHandstandPushUp->getName());
        $this->assertMovementHasMuscles($wallFacingStrictHandstandPushUp, [
            MuscleData::MUSCLE_DELTOIDS,
            MuscleData::MUSCLE_TRICEPS,
            MuscleData::MUSCLE_RECTUS_ABDOMINIS,
            MuscleData::MUSCLE_OBLIQUES,
            MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
        ]);

        /** @var Movement $sledPush */
        $sledPush = $this->getReference(MovementData::MOVEMENT_SLED_PUSH, Movement::class);

        self::assertTrue(
            $sledPush->getMovementExecutionTimeForMeasureUnits()->exists(
                static fn (int $index, $executionTime): bool => $executionTime->getMeasureUnit() === MeasureUnitEnum::METER
            )
        );
    }

    /**
     * @param list<string> $expectedMuscleReferences
     */
    private function assertMovementHasMuscles(Movement $movement, array $expectedMuscleReferences): void
    {
        foreach ($expectedMuscleReferences as $expectedMuscleReference) {
            /** @var Muscle $muscle */
            $muscle = $this->getReference($expectedMuscleReference, Muscle::class);

            self::assertTrue(
                $movement->getMuscles()->contains($muscle),
                sprintf('Expected movement "%s" to contain muscle "%s".', $movement->getName(), $muscle->getName())
            );
        }
    }
}
