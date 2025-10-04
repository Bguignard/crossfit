<?php

namespace App\DataFixtures;

use App\Entity\Workout\Enum\WorkoutMovementGenerationTypeEnum;
use App\Entity\Workout\WorkoutMovementGenerationType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class WorkoutMovementGenerationTypeData extends Fixture
{
    public const string WORKOUT_MOVEMENT_GENERATION_TYPE_BODY_PART = 'workout-movement-generation-type-body-part';
    public const string WORKOUT_MOVEMENT_GENERATION_TYPE_MOVEMENT = 'workout-movement-generation-type-movement';

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getWorkoutMovementGenerationTypes() as $reference => $workoutMovementGenerationType) {
            $workoutMovementGenerationTypeEntity = new WorkoutMovementGenerationType($workoutMovementGenerationType);
            $manager->persist($workoutMovementGenerationTypeEntity);
            $this->addReference($reference, $workoutMovementGenerationTypeEntity);
        }
        $manager->flush();
    }

    private function getWorkoutMovementGenerationTypes(): array
    {
        return [
            self::WORKOUT_MOVEMENT_GENERATION_TYPE_BODY_PART => WorkoutMovementGenerationTypeEnum::BODY_PART,
            self::WORKOUT_MOVEMENT_GENERATION_TYPE_MOVEMENT => WorkoutMovementGenerationTypeEnum::MOVEMENT,
        ];
    }
}
