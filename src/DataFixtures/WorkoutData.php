<?php

namespace App\DataFixtures;

use App\Entity\Workout\Block;
use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\MovementCluster;
use App\Entity\Workout\Workout;
use App\Entity\Workout\WorkoutOrigin;
use App\Enum\RepUnit;
use App\Enum\WorkoutOriginName;
use App\Enum\WorkoutType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class WorkoutData extends Fixture implements DependentFixtureInterface
{
    public const WORKOUT_FRAN = 'workout-fran';
    public const WORKOUT_OPEN_17_5 = 'workout-open-17-5';

    public function getDependencies(): array
    {
        return [
            ImplementData::class,
            MovementData::class,
        ];
    }

    public function load(ObjectManager $manager)
    {
        foreach ($this->getWorkouts() as $workout) {
            $blocks = [];
            foreach ($workout['blocks'] as $block) {
                $clusters = array_map(function ($movementCluster) {
                    return new MovementCluster(
                        $movementCluster['repetitions'],
                        $this->getImplementsArrayFromReferences($movementCluster['implements']),
                        $this->getReference($movementCluster['movement'], Movement::class),
                        $movementCluster['movementIntensity'],
                        $movementCluster['repUnit'],
                    );
                }, $block['movementClusters']);

                $block = new Block(
                    $block['rounds'],
                    $block['orderInWorkout'],
                    $clusters,
                    $block['restTime'],
                );
                $blocks[] = $block;
            }
            $workoutObject = new Workout(
                $workout['name'],
                $workout['numberOfRounds'],
                $workout['timeCap'],
                $workout['workoutType'],
                new WorkoutOrigin(
                    $workout['workoutOrigin']['name'],
                    $workout['workoutOrigin']['year'],
                ),
                $blocks,
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
                'blocks' => [
                    [
                        'rounds' => 1,
                        'orderInWorkout' => 1,
                        'restTime' => null,
                        'movementClusters' => [
                            [
                                'repetitions' => 21,
                                'movement' => MovementData::MOVEMENT_THRUSTER,
                                'movementIntensity' => 43,
                                'repUnit' => RepUnit::KILOGRAM,
                                'implements' => [
                                    ImplementData::IMPLEMENT_BARBELL,
                                ],
                            ],
                            [
                                'repetitions' => 21,
                                'movement' => MovementData::MOVEMENT_PULL_UP,
                                'movementIntensity' => null,
                                'repUnit' => RepUnit::REPETITION,
                                'implements' => [
                                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                                ],
                            ],
                        ],
                    ],
                    [
                        'rounds' => 1,
                        'orderInWorkout' => 2,
                        'restTime' => null,
                        'movementClusters' => [
                            [
                                'repetitions' => 15,
                                'movement' => MovementData::MOVEMENT_THRUSTER,
                                'movementIntensity' => 43,
                                'repUnit' => RepUnit::KILOGRAM,
                                'implements' => [
                                    ImplementData::IMPLEMENT_BARBELL,
                                ],
                            ],
                            [
                                'repetitions' => 15,
                                'movement' => MovementData::MOVEMENT_PULL_UP,
                                'movementIntensity' => null,
                                'repUnit' => RepUnit::REPETITION,
                                'implements' => [
                                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                                ],
                            ],
                        ],
                    ],
                    [
                        'rounds' => 1,
                        'orderInWorkout' => 3,
                        'restTime' => null,
                        'movementClusters' => [
                            [
                                'repetitions' => 9,
                                'movement' => MovementData::MOVEMENT_THRUSTER,
                                'movementIntensity' => 43,
                                'repUnit' => RepUnit::KILOGRAM,
                                'implements' => [
                                    ImplementData::IMPLEMENT_BARBELL,
                                ],
                            ],
                            [
                                'repetitions' => 9,
                                'movement' => MovementData::MOVEMENT_PULL_UP,
                                'movementIntensity' => null,
                                'repUnit' => RepUnit::REPETITION,
                                'implements' => [
                                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                                ],
                            ],
                        ],
                    ],
                ],
                'numberOfRounds' => 1,
                'timeCap' => 10,
                'workoutType' => WorkoutType::ForTime,
                'workoutOrigin' => [
                    'name' => WorkoutOriginName::GIRLS_WORKOUT,
                    'year' => 2010,
                ],
            ],
            // Open 17.5
            [
                'reference' => self::WORKOUT_OPEN_17_5,
                'name' => 'Open 17.5',
                'blocks' => [
                    [
                        'rounds' => 1,
                        'orderInWorkout' => 1,
                        'restTime' => null,
                        'movementClusters' => [
                            [
                                'repetitions' => 9,
                                'movement' => MovementData::MOVEMENT_THRUSTER,
                                'movementIntensity' => 43,
                                'repUnit' => RepUnit::KILOGRAM,
                                'implements' => [
                                    ImplementData::IMPLEMENT_BARBELL,
                                ],
                            ],
                            [
                                'repetitions' => 35,
                                'movement' => MovementData::MOVEMENT_DOUBLE_UNDER,
                                'movementIntensity' => null,
                                'repUnit' => RepUnit::REPETITION,
                                'implements' => [
                                    ImplementData::IMPLEMENT_JUMP_ROPE,
                                ],
                            ],
                        ],
                    ],
                ],
                'numberOfRounds' => 10,
                'timeCap' => 40,
                'workoutType' => WorkoutType::ForTime,
                'workoutOrigin' => [
                    'name' => WorkoutOriginName::CROSSFIT_OPEN_WORKOUT,
                    'year' => 2017,
                ],
            ],
        ];
    }

    private function getImplementsArrayFromReferences(array $implements): array
    {
        return array_map(function ($implement) {
            return $this->getReference($implement, Implement::class);
        }, $implements);
    }
}
