<?php

namespace App\DataFixtures;

use App\Entity\Workout\Enum\WorkoutTypeEnum;
use App\Entity\Workout\WorkoutType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class WorkoutTypeData extends Fixture
{
    public const string WORKOUT_TYPE_FOR_TIME = 'workout-type-for-time';
    public const string WORKOUT_TYPE_AMRAP = 'workout-type-amrap';
    public const string WORKOUT_TYPE_FOR_WEIGHT = 'workout-type-for-weight';
    public const string WORKOUT_TYPE_INTERVALS = 'workout-type-intervals';

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getWorkoutType() as $reference => $workoutType) {
            $workoutTypeEntity = new WorkoutType($workoutType['name']);
            $manager->persist($workoutTypeEntity);
            $this->addReference($reference, $workoutTypeEntity);
        }
        $manager->flush();
    }

    private function getWorkoutType(): array
    {
        return [
            self::WORKOUT_TYPE_FOR_TIME => [
                'name' => WorkoutTypeEnum::FOR_TIME,
            ],
            self::WORKOUT_TYPE_AMRAP => [
                'name' => WorkoutTypeEnum::AMRAP,
            ],
            self::WORKOUT_TYPE_FOR_WEIGHT => [
                'name' => WorkoutTypeEnum::FOR_WEIGHT,
            ],
            self::WORKOUT_TYPE_INTERVALS => [
                'name' => WorkoutTypeEnum::INTERVALS,
            ],
        ];
    }
}
