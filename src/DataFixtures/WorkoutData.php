<?php

namespace App\DataFixtures;

use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\Workout;
use App\Entity\Workout\WorkoutOrigin;
use App\Entity\Workout\WorkoutOriginName;
use App\Entity\Workout\WorkoutType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class WorkoutData extends Fixture implements DependentFixtureInterface
{
    public const string WORKOUT_FRAN = 'workout-fran';
    public const string WORKOUT_OPEN_17_5 = 'workout-open-17-5';

    public function getDependencies(): array
    {
        return [
            ImplementData::class,
            MovementData::class,
            ImplementTypeOfAdjustableMeasureUnitData::class,
            WorkoutTypeData::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getWorkouts() as $workout) {
            $workoutObject = new Workout(
                $workout['name'],
                $workout['flow'],
                $workout['numberOfRounds'],
                $workout['timeCap'],
                $this->getReference($workout['workoutType'], WorkoutType::class),
                new WorkoutOrigin(
                    $this->getReference($workout['workoutOrigin']['name'], WorkoutOriginName::class),
                    $workout['workoutOrigin']['year'],
                ),
                $this->getImplementsArrayFromReferences($workout['implements']),
                $this->getMovementsArrayFromReferences($workout['movements']),
            );
            $manager->persist($workoutObject);
            $this->addReference($workout['reference'], $workoutObject);
        }
        $manager->flush();
    }

    private function getWorkouts(): array
    {
        return [
            // Fran
            [
                'reference' => self::WORKOUT_FRAN,
                'name' => 'Fran',
                'flow' => <<<'WOD'
                    For time:
                    21-15-9
                    Thrusters (43 kg)
                    Pull-ups
                    WOD,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movements' => [
                    MovementData::MOVEMENT_THRUSTER,
                    MovementData::MOVEMENT_PULL_UP,
                ],
                'numberOfRounds' => 1,
                'timeCap' => 10,
                'workoutType' => WorkoutTypeData::WORKOUT_TYPE_FOR_TIME,
                'workoutOrigin' => [
                    'name' => WorkoutOriginNameData::WORKOUT_ORIGIN_NAME_GIRLS_WORKOUT,
                    'year' => 2010,
                ],
            ],
            // Open 17.5
            [
                'reference' => self::WORKOUT_OPEN_17_5,
                'name' => 'Open 17.5',
                'flow' => <<<'WOD'
                    10 rounds for time:
                    9 thrusters (43 kg)
                    35 double-unders
                    WOD,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_JUMP_ROPE,
                ],
                'movements' => [
                    MovementData::MOVEMENT_THRUSTER,
                    MovementData::MOVEMENT_DOUBLE_UNDER,
                ],
                'numberOfRounds' => 10,
                'timeCap' => 40,
                'workoutType' => WorkoutTypeData::WORKOUT_TYPE_FOR_TIME,
                'workoutOrigin' => [
                    'name' => WorkoutOriginNameData::WORKOUT_ORIGIN_NAME_CROSSFIT_OPEN_WORKOUT,
                    'year' => 2017,
                ],
            ],
        ];
    }

    private function getMovementsArrayFromReferences(array $movements): array
    {
        return array_map(function ($movement) {
            return $this->getReference($movement, Movement::class);
        }, $movements);
    }

    private function getImplementsArrayFromReferences(array $implements): array
    {
        return array_map(function ($implement) {
            return $this->getReference($implement, Implement::class);
        }, $implements);
    }
}
