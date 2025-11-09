<?php

namespace App\Tests;

use App\DataFixtures\BodyPartData;
use App\DataFixtures\ImplementData;
use App\DataFixtures\MovementDifficultyData;
use App\DataFixtures\MovementTypeData;
use App\DataFixtures\WorkoutMovementGenerationTypeData;
use App\DataFixtures\WorkoutTypeData;
use App\Entity\Workout\BodyPart;
use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\MovementDifficulty;
use App\Entity\Workout\MovementType;
use App\Entity\Workout\WorkoutType;
use App\Entity\Workout\WorkoutMovementGenerationType;
use App\Entity\WorkoutGeneration\WorkoutGeneration;
use App\Services\Workout\MovementServiceInterface;

class MovementServiceTest extends AbstractIntegrationTest
{
    private MovementServiceInterface $movementService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->movementService = $this->getService(MovementServiceInterface::class);
    }

    private function buildBasicWorkoutGeneration(int $count = 2): WorkoutGeneration
    {
        /** @var MovementType $gym */
        $gym = $this->getReference(MovementTypeData::MOVEMENT_TYPE_GYMNASTIC, MovementType::class);
        /** @var MovementType $weight */
        $weight = $this->getReference(MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING, MovementType::class);
        /** @var WorkoutType $forTime */
        $forTime = $this->getReference(WorkoutTypeData::WORKOUT_TYPE_FOR_TIME, WorkoutType::class);
        /** @var MovementDifficulty $rx */
        $rx = $this->getReference(MovementDifficultyData::MOVEMENT_DIFFICULTY_RX, MovementDifficulty::class);
        /** @var WorkoutMovementGenerationType $genType */
        $genType = $this->getReference(WorkoutMovementGenerationTypeData::WORKOUT_MOVEMENT_GENERATION_TYPE_MOVEMENT, WorkoutMovementGenerationType::class);
        /** @var Implement $barbell */
        $barbell = $this->getReference(ImplementData::IMPLEMENT_BARBELL, Implement::class);
        /** @var Implement $pullUpBar */
        $pullUpBar = $this->getReference(ImplementData::IMPLEMENT_PULL_UP_BAR, Implement::class);

        $wg = (new WorkoutGeneration())
            ->setName('WG Test')
            ->setTimeCap(10)
            ->setNumberOfDifferentMovements($count)
            ->setWorkoutType($forTime)
            ->setMovementDifficulty($rx)
            ->setMovementGenerationType($genType)
        ;
        $wg->setMovementTypes([$gym, $weight]);
        $wg->setAvailableImplements([$barbell, $pullUpBar]);

        return $wg;
    }

    public function testRemoveNotAvailableImplementsFromMovementsOfWorkout(): void
    {
        $wg = $this->buildBasicWorkoutGeneration(2);
        $movements = $this->movementService->getWorkoutMovementsFromWorkoutGeneration($wg);

        // Keep only BARBELL as available implement
        /** @var Implement $barbell */
        $barbell = $this->getReference(ImplementData::IMPLEMENT_BARBELL, Implement::class);
        $filtered = $this->movementService->removeNotAvailableImplementsFromMovementsOfWorkout($wg->getAvailableImplements()->filter(static fn($i) => $i === $barbell), $movements);

        foreach ($filtered as $movement) {
            foreach ($movement->getPossibleImplements() as $implement) {
                self::assertTrue($implement->getId()->equals($barbell->getId()), 'Only allowed implements should remain');
            }
        }
    }

    public function testGetSimpleWorkoutMovementsFromPossibleMovementsRespectsMandatoryAndCount(): void
    {
        $wg = $this->buildBasicWorkoutGeneration(2);

        // Make one movement mandatory then ensure it is kept and count is satisfied
        /** @var Movement $thruster */
        $thruster = $this->getRepository(Movement::class)->findOneBy(['name' => 'THRUSTER']);
        if ($thruster) {
            $wg->addMandatoryMovement($thruster);
        }

        $possible = $this->movementService->getWorkoutMovementsFromWorkoutGeneration($wg);
        $result = $this->movementService->getSimpleWorkoutMovementsFromPossibleMovements($possible, $wg);

        self::assertCount($wg->getNumberOfDifferentMovements(), $result);
        if ($thruster) {
            self::assertTrue(in_array($thruster, $result, true), 'Mandatory movement should be included');
        }
    }

    public function testGetWorkoutMovementsFromWorkoutGenerationReturnsExpectedCount(): void
    {
        $wg = $this->buildBasicWorkoutGeneration(3);

        $movements = $this->movementService->getWorkoutMovementsFromWorkoutGeneration($wg);

        self::assertIsArray($movements);
        self::assertNotEmpty($movements);
        self::assertLessThanOrEqual($wg->getNumberOfDifferentMovements(), count($movements));
    }

    public function testGetMovementsFromMusclesUsesBodyParts(): void
    {
        $wg = $this->buildBasicWorkoutGeneration(2);
        /** @var BodyPart $legs */
        $legs = $this->getReference(BodyPartData::BODY_PART_LEGS, BodyPart::class);
        /** @var BodyPart $shoulders */
        $shoulders = $this->getReference(BodyPartData::BODY_PART_SHOULDERS, BodyPart::class);
        $wg->setMandatoryBodyParts([$legs, $shoulders]);

        $movements = $this->movementService->getMovementsFromMuscles($wg);

        self::assertIsArray($movements);
        self::assertNotEmpty($movements);
        self::assertLessThanOrEqual($wg->getNumberOfDifferentMovements(), count($movements));
    }
}
