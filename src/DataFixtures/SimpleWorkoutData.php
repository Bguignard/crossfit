<?php

namespace App\DataFixtures;

use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\SimpleWorkout;
use App\Entity\Workout\WorkoutOrigin;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SimpleWorkoutData extends Fixture implements DependentFixtureInterface
{
    public const string SIMPLE_WORKOUT_ANGIE = 'Angie';
    public const string SIMPLE_WORKOUT_FRAN = 'Fran';
    public const string SIMPLE_WORKOUT_BARBARA = 'Barbara';
    public const string SIMPLE_WORKOUT_CHELSEA = 'Chelsea';
    public const string SIMPLE_WORKOUT_DIANE = 'Diane';
    public const string SIMPLE_WORKOUT_ELIZABETH = 'Elizabeth';
    public const string SIMPLE_WORKOUT_GRACE = 'Grace';
    public const string SIMPLE_WORKOUT_HELEN = 'Helen';
    public const string SIMPLE_WORKOUT_JACKIE = 'Jackie';
    public const string SIMPLE_WORKOUT_KAREN = 'Karen';
    public const string SIMPLE_WORKOUT_LINDA = 'Linda';
    public const string SIMPLE_WORKOUT_MARY = 'Mary';
    public const string SIMPLE_WORKOUT_NANCY = 'Nancy';
    public const string SIMPLE_WORKOUT_ANNIE = 'Annie';
    public const string SIMPLE_WORKOUT_ISABEL = 'Isabel';
    public const string SIMPLE_WORKOUT_EVA = 'Eva';
    public const string SIMPLE_WORKOUT_KELLY = 'Kelly';
    public const string SIMPLE_WORKOUT_NICOLE = 'Nicole';
    public const string SIMPLE_WORKOUT_LYNNE = 'Lynne';
    public const string SIMPLE_WORKOUT_CINDY = 'Cindy';
    public const string SIMPLE_WORKOUT_AMANDA = 'Amanda';

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getWorkouts() as $reference => $workout) {
            $implements = [];
            $movements = [];
            foreach ($workout['implements'] as $implementReference) {
                $implements[] = $this->getReference($implementReference, Implement::class);
            }
            foreach ($workout['movements'] as $movement) {
                $movements[] = $this->getReference($movement, Movement::class);
            }

            $simpleWorkout = new SimpleWorkout(
                $workout['name'],
                $workout['flow'],
                $workout['timeCap'] ?? null,
                $this->getReference($workout['origin'], WorkoutOrigin::class),
                $implements,
                $movements
            );
            $manager->persist($simpleWorkout);
            $this->addReference($reference, $simpleWorkout);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            WorkoutOriginData::class,
            MovementData::class,
            ImplementData::class,
        ];
    }

    public function getWorkouts(): array
    {
        return [
            self::SIMPLE_WORKOUT_ANGIE => [
                'name' => 'Angie',
                'flow' => <<<TXT
                For time:
                100 Pull-Ups
                100 Push-Ups
                100 Sit-Ups
                100 Air Squats
                TXT,
                'timeCap' => 30,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_SIT_UP,
                    MovementData::MOVEMENT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_FRAN => [
                'name' => 'Fran',
                'flow' => <<<TXT
                For time:
                21-15-9
                Thrusters (95/65 lb)
                Pull-Ups
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_BARBELL, ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_THRUSTER,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_BARBARA => [
                'name' => 'Barbara',
                'flow' => <<<TXT
                5 rounds for time:
                20 Pull-Ups
                30 Push-Ups
                40 Sit-Ups
                50 Air Squats
                Rest 3 min between rounds
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_SIT_UP,
                    MovementData::MOVEMENT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_CHELSEA => [
                'name' => 'Chelsea',
                'flow' => <<<TXT
                EMOM 30 min:
                5 Pull-Ups
                10 Push-Ups
                15 Air Squats
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_DIANE => [
                'name' => 'Diane',
                'flow' => <<<TXT
                For time:
                21-15-9
                Deadlifts (225/155 lb)
                Handstand Push-Ups
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_BARBELL],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_HANDSTAND_PUSH_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_ELIZABETH => [
                'name' => 'Elizabeth',
                'flow' => <<<TXT
                For time:
                21-15-9
                Cleans (135/95 lb)
                Ring Dips
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_BARBELL, ImplementData::IMPLEMENT_RINGS],
                'movements' => [
                    MovementData::MOVEMENT_CLEAN,
                    MovementData::MOVEMENT_DIP,
                ],
            ],
            self::SIMPLE_WORKOUT_GRACE => [
                'name' => 'Grace',
                'flow' => <<<TXT
                For time:
                30 Clean and Jerks (135/95 lb)
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_BARBELL],
                'movements' => [
                    MovementData::MOVEMENT_CLEAN_AND_JERK,
                ],
            ],
            self::SIMPLE_WORKOUT_HELEN => [
                'name' => 'Helen',
                'flow' => <<<TXT
                3 rounds for time:
                400 m Run
                21 Kettlebell Swings (53/35 lb)
                12 Pull-Ups
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_KETTLEBELL, ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_AMERICAN_SWING,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_JACKIE => [
                'name' => 'Jackie',
                'flow' => <<<TXT
                For time:
                1000 m Row
                50 Thrusters (45/35 lb)
                30 Pull-Ups
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_ROWER, ImplementData::IMPLEMENT_BARBELL, ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_ROW,
                    MovementData::MOVEMENT_THRUSTER,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_KAREN => [
                'name' => 'Karen',
                'flow' => <<<TXT
                For time:
                150 Wall-Ball Shots (20/14 lb)
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_MEDICINE_BALL],
                'movements' => [
                    MovementData::MOVEMENT_WALL_BALL_SHOT,
                ],
            ],
            self::SIMPLE_WORKOUT_LINDA => [
                'name' => 'Linda',
                'flow' => <<<TXT
                For time (10-9-8-...-1):
                Deadlift (1.5x BW)
                Bench Press (1x BW)
                Clean (0.75x BW)
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_BARBELL],
                'movements' => [
                    MovementData::MOVEMENT_DEADLIFT,
                    MovementData::MOVEMENT_BENCH_PRESS,
                    MovementData::MOVEMENT_CLEAN,
                ],
            ],
            self::SIMPLE_WORKOUT_MARY => [
                'name' => 'Mary',
                'flow' => <<<TXT
                AMRAP 20:
                5 Handstand Push-Ups
                10 Pistols (alternating)
                15 Pull-Ups
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_HANDSTAND_PUSH_UP,
                    MovementData::MOVEMENT_ALTERNATE_PISTOL_SQUAT,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_NANCY => [
                'name' => 'Nancy',
                'flow' => <<<TXT
                5 rounds for time:
                400 m Run
                15 Overhead Squats (95/65 lb)
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_BARBELL],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_OVERHEAD_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_ANNIE => [
                'name' => 'Annie',
                'flow' => <<<TXT
                For time:
                50-40-30-20-10
                Double-Unders
                Sit-Ups
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_JUMP_ROPE],
                'movements' => [
                    MovementData::MOVEMENT_DOUBLE_UNDER,
                    MovementData::MOVEMENT_SIT_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_ISABEL => [
                'name' => 'Isabel',
                'flow' => <<<TXT
                For time:
                30 Snatches (135/95 lb)
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_BARBELL],
                'movements' => [
                    MovementData::MOVEMENT_SNATCH,
                ],
            ],
            self::SIMPLE_WORKOUT_EVA => [
                'name' => 'Eva',
                'flow' => <<<TXT
                5 rounds for time:
                800 m Run
                30 Kettlebell Swings (70/53 lb)
                30 Pull-Ups
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_KETTLEBELL, ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_AMERICAN_SWING,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_KELLY => [
                'name' => 'Kelly',
                'flow' => <<<TXT
                5 rounds for time:
                400 m Run
                30 Box Jumps (24/20 in)
                30 Wall-Ball Shots (20/14 lb)
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_BOX, ImplementData::IMPLEMENT_MEDICINE_BALL],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_BOX_JUMP,
                    MovementData::MOVEMENT_WALL_BALL_SHOT,
                ],
            ],
            self::SIMPLE_WORKOUT_NICOLE => [
                'name' => 'Nicole',
                'flow' => <<<TXT
                AMRAP 20:
                400 m Run
                Max Rep Pull-Ups (each round)
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_RUN,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_LYNNE => [
                'name' => 'Lynne',
                'flow' => <<<TXT
                5 rounds (not for time):
                Max Rep Bodyweight Bench Press
                Max Rep Pull-Ups
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_BARBELL, ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_BENCH_PRESS,
                    MovementData::MOVEMENT_PULL_UP,
                ],
            ],
            self::SIMPLE_WORKOUT_CINDY => [
                'name' => 'Cindy',
                'flow' => <<<TXT
                AMRAP 20:
                5 Pull-Ups
                10 Push-Ups
                15 Air Squats
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_PULL_UP_BAR],
                'movements' => [
                    MovementData::MOVEMENT_PULL_UP,
                    MovementData::MOVEMENT_PUSH_UP,
                    MovementData::MOVEMENT_SQUAT,
                ],
            ],
            self::SIMPLE_WORKOUT_AMANDA => [
                'name' => 'Amanda',
                'flow' => <<<TXT
                For time:
                9-7-5
                Muscle-Ups
                Snatches (135/95 lb)
                TXT,
                'origin' => WorkoutOriginData::WORKOUT_ORIGIN_GIRLS,
                'implements' => [ImplementData::IMPLEMENT_RINGS, ImplementData::IMPLEMENT_BARBELL],
                'movements' => [
                    MovementData::MOVEMENT_MUSCLE_UP,
                    MovementData::MOVEMENT_SNATCH,
                ],
            ],
        ];
    }
}
