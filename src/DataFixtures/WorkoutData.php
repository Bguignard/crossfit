<?php

namespace App\DataFixtures;

use App\Entity\Workout\Block;
use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\MovementCluster;
use App\Entity\Workout\MovementDetail;
use App\Entity\Workout\Workout;
use App\Entity\Workout\WorkoutOrigin;
use App\Enum\RepUnitEnum;
use App\Enum\WorkoutOriginNameEnum;
use App\Enum\WorkoutTypeEnum;
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
                        $movementCluster['repUnit'],
                        $this->getImplementsArrayFromReferences($movementCluster['implements']),
                        $this->getReference($movementCluster['movement'], Movement::class),
                        $movementCluster['movementDetail'] ?
                            new MovementDetail(
                                $movementCluster['movementDetail']['movementIntensity'],
                                $movementCluster['movementDetail']['movementIntensityUnit'],
                            ) : null,
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
                                'repUnit' => RepUnitEnum::REPETITION,
                                'movement' => MovementData::MOVEMENT_THRUSTER,
                                'movementDetail' => [
                                    'movementIntensity' => 43,
                                    'movementIntensityUnit' => RepUnitEnum::KILOGRAM,
                                ],
                                'implements' => [
                                    ImplementData::IMPLEMENT_BARBELL,
                                ],
                            ],
                            [
                                'repetitions' => 21,
                                'repUnit' => RepUnitEnum::REPETITION,
                                'movement' => MovementData::MOVEMENT_PULL_UP,
                                'movementDetail' => null,
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
                                'repUnit' => RepUnitEnum::REPETITION,
                                'movement' => MovementData::MOVEMENT_THRUSTER,
                                'movementDetail' => [
                                    'movementIntensity' => 43,
                                    'movementIntensityUnit' => RepUnitEnum::KILOGRAM,
                                    ],
                                'implements' => [
                                    ImplementData::IMPLEMENT_BARBELL,
                                ],
                            ],
                            [
                                'repetitions' => 15,
                                'repUnit' => RepUnitEnum::REPETITION,
                                'movement' => MovementData::MOVEMENT_PULL_UP,
                                'movementDetail' => null,
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
                                'repUnit' => RepUnitEnum::REPETITION,
                                'movement' => MovementData::MOVEMENT_THRUSTER,
                                'movementDetail' => [
                                    'movementIntensity' => 43,
                                    'movementIntensityUnit' => RepUnitEnum::KILOGRAM,
                                ],
                                'implements' => [
                                    ImplementData::IMPLEMENT_BARBELL,
                                ],
                            ],
                            [
                                'repetitions' => 9,
                                'repUnit' => RepUnitEnum::REPETITION,
                                'movement' => MovementData::MOVEMENT_PULL_UP,
                                'movementDetail' => null,
                                'implements' => [
                                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                                ],
                            ],
                        ],
                    ],
                ],
                'numberOfRounds' => 1,
                'timeCap' => 10,
                'workoutType' => WorkoutTypeEnum::FOR_TIME,
                'workoutOrigin' => [
                    'name' => WorkoutOriginNameEnum::GIRLS_WORKOUT,
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
                                'repUnit' => RepUnitEnum::REPETITION,
                                'movement' => MovementData::MOVEMENT_THRUSTER,
                                'movementDetail' => [
                                    'movementIntensity' => 43,
                                    'movementIntensityUnit' => RepUnitEnum::KILOGRAM,
                                ],
                                'implements' => [
                                    ImplementData::IMPLEMENT_BARBELL,
                                ],
                            ],
                            [
                                'repetitions' => 35,
                                'repUnit' => RepUnitEnum::REPETITION,
                                'movement' => MovementData::MOVEMENT_DOUBLE_UNDER,
                                'movementDetail' => null,
                                'implements' => [
                                    ImplementData::IMPLEMENT_JUMP_ROPE,
                                ],
                            ],
                        ],
                    ],
                ],
                'numberOfRounds' => 10,
                'timeCap' => 40,
                'workoutType' => WorkoutTypeEnum::FOR_TIME,
                'workoutOrigin' => [
                    'name' => WorkoutOriginNameEnum::CROSSFIT_OPEN_WORKOUT,
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
