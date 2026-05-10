<?php

namespace App\Tests;

use App\DataFixtures\ImplementData;
use App\DataFixtures\MovementData;
use App\DataFixtures\WorkoutData;
use App\Entity\Workout\Enum\ImplementEnum;
use App\Entity\Workout\Enum\MeasureUnitEnum;
use App\Entity\Workout\Enum\MovementDifficultyEnum;
use App\Entity\Workout\Enum\MovementTypeEnum;
use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use App\Entity\Workout\Enum\WorkoutTypeEnum;
use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\Workout;

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
}
