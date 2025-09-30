<?php

namespace App\DataFixtures;

use App\Entity\Workout\Enum\MeasureUnitEnum;
use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\MovementDifficulty;
use App\Entity\Workout\MovementExecutionTimeForMeasureUnit;
use App\Entity\Workout\MovementType;
use App\Entity\Workout\Muscle;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MovementData extends Fixture implements DependentFixtureInterface
{
    public const string MOVEMENT_BENCH_PRESS = 'movement-bench-press';
    public const string MOVEMENT_INCLINE_BENCH_PRESS = 'movement-incline-bench-press';
    public const string MOVEMENT_PULL_UP = 'movement-pull-up';
    public const string MOVEMENT_DEADLIFT = 'movement-deadlift';
    public const string MOVEMENT_SHOULDER_PRESS = 'movement-shoulder-press';
    public const string MOVEMENT_CHIN_UP = 'movement-chin-up';
    public const string MOVEMENT_CHEST_TO_BAR_PULL_UP = 'movement-chest-to-bar-pull-up';
    public const string MOVEMENT_THRUSTER = 'movement-thruster';
    public const string MOVEMENT_PUSH_PRESS = 'movement-push-press';
    public const string MOVEMENT_PUSH_JERK = 'movement-push-jerk';
    public const string MOVEMENT_SQUAT_CLEAN = 'movement-squat-clean';
    public const string MOVEMENT_POWER_CLEAN = 'movement-power-clean';
    public const string MOVEMENT_CLEAN = 'movement-clean';
    public const string MOVEMENT_HANG_POWER_CLEAN = 'movement-hang-power-clean';
    public const string MOVEMENT_HANG_SQUAT_CLEAN = 'movement-hang-squat-clean';
    public const string MOVEMENT_LOW_HANG_POWER_CLEAN = 'movement-low-hang-power-clean';
    public const string MOVEMENT_LOW_HANG_SQUAT_CLEAN = 'movement-low-hang-squat-clean';
    public const string MOVEMENT_HIGH_HANG_POWER_CLEAN = 'movement-high-hang-power-clean';
    public const string MOVEMENT_HIGH_HANG_SQUAT_CLEAN = 'movement-high-hang-squat-clean';
    public const string MOVEMENT_SQUAT_SNATCH = 'movement-squat-snatch';
    public const string MOVEMENT_POWER_SNATCH = 'movement-power-snatch';
    public const string MOVEMENT_SNATCH = 'movement-snatch';
    public const string MOVEMENT_HANG_POWER_SNATCH = 'movement-hang-snatch';
    public const string MOVEMENT_HANG_SQUAT_SNATCH = 'movement-hang-squat-snatch';
    public const string MOVEMENT_MUSCLE_CLEAN = 'movement-muscle-clean';
    public const string MOVEMENT_MUSCLE_SNATCH = 'movement-muscle-snatch';
    public const string MOVEMENT_OVERHEAD_SQUAT = 'movement-overhead-squat';
    public const string MOVEMENT_FRONT_RACK_WALKING_LUNGE = 'movement-front-rack-walking-lunge';
    public const string MOVEMENT_OVERHEAD_WALKING_LUNGE = 'movement-overhead-walking-lunge';
    public const string MOVEMENT_BACK_RACK_WALKING_LUNGE = 'movement-back-rack-walking-lunge';
    public const string MOVEMENT_SQUAT = 'movement-squat';
    public const string MOVEMENT_FRONT_SQUAT = 'movement-front-squat';
    public const string MOVEMENT_BACK_SQUAT = 'movement-back-squat';
    public const string MOVEMENT_SINGLE_UNDER = 'movement-single-under';
    public const string MOVEMENT_DOUBLE_UNDER = 'movement-double-under';
    public const string MOVEMENT_CROSS_OVER = 'movement-cross-over';
    public const string MOVEMENT_BOX_JUMP_OVER = 'movement-box-jump-over';
    public const string MOVEMENT_BOX_JUMP = 'movement-box-jump';
    public const string MOVEMENT_WALL_BALL_SHOT = 'movement-wall-ball-shot';
    public const string MOVEMENT_AMERICAN_SWING = 'movement-american_swing';
    public const string MOVEMENT_RUSSIAN_SWING = 'movement-russian_swing';
    public const string MOVEMENT_RUN = 'movement-run';
    public const string MOVEMENT_ROW = 'movement-row';
    public const string MOVEMENT_BIKE_ERG = 'movement-bike-erg';
    public const string MOVEMENT_ASSAULT_BIKE = 'movement-assault-bike';
    public const string MOVEMENT_SKI_ERG = 'movement-ski-erg';
    public const string MOVEMENT_BURPEE = 'movement-burpee';
    public const string MOVEMENT_BURPEE_BOX_JUMP_OVER = 'movement-burpee-box-jump-over';
    public const string MOVEMENT_BURPEE_OVER = 'movement-burpee-over';
    public const string MOVEMENT_BURPEE_OVER_FACING = 'movement-burpee-over-facing';
    public const string MOVEMENT_BURPEE_PULL_UP = 'movement-burpee-pull-up';
    public const string MOVEMENT_BURPEE_MUSCLE_UP = 'movement-burpee-muscle-up';
    public const string MOVEMENT_BURPEE_CHEST_TO_BAR_PULL_UP = 'movement-burpee-chest-to-bar-pull-up';
    public const string MOVEMENT_STRICT_CHEST_TO_BAR_PULL_UP = 'movement-strict_chest-to-bar-pull-up';
    public const string MOVEMENT_STRICT_PULL_UP = 'movement-strict-pull-up';
    public const string MOVEMENT_MUSCLE_UP = 'movement-muscle-up';
    public const string MOVEMENT_PULL_OVER = 'movement-pull-over';
    public const string MOVEMENT_DIP = 'movement-dip';
    public const string MOVEMENT_HANDSTAND_PUSH_UP = 'movement-handstand-push-up';
    public const string MOVEMENT_BURPEE_TARGET = 'movement-burpee-target';
    public const string MOVEMENT_BURPEE_PULL_OVER = 'movement-burpee-pull-over';
    public const string MOVEMENT_WALL_FACING_HANDSTAND_PUSH_UP = 'movement-wall-facing-handstand-push-up';
    public const string MOVEMENT_WALL_FACING_STRICT_HANDSTAND_PUSH_UP = 'movement-wall-facing-strict-handstand-push-up';
    public const string MOVEMENT_WALL_FACING_DEFICIT_HANDSTAND_PUSH_UP = 'movement-wall-facing-deficit-handstand-push-up';
    public const string MOVEMENT_WALL_FACING_DEFICIT_STRICT_HANDSTAND_PUSH_UP = 'movement-wall-facing-deficit-strict-handstand-push-up';
    public const string MOVEMENT_HANDSTAND_PIROUETTE = 'movement-handstand-pirouette';
    public const string MOVEMENT_HANDSTAND_WALK = 'movement-handstand-walk';
    public const string MOVEMENT_STRICT_HANDSTAND_PUSH_UP = 'movement-strict-handstand-push-up';
    public const string MOVEMENT_TOES_TO_BAR = 'movement-toes-to-bar';
    public const string MOVEMENT_TOES_TO_RING = 'movement-toes-to-ring';
    public const string MOVEMENT_STRICT_TOES_TO_BAR = 'movement-strict-toes-to-bar';
    public const string MOVEMENT_KNEES_TO_ELBOWS = 'movement-knees-to-elbows';
    public const string MOVEMENT_KNEES_RAISE = 'movement-knees-raise';
    public const string MOVEMENT_ALTERNATE_PISTOL_SQUAT = 'movement-alternate-pistol-squat';
    public const string MOVEMENT_PISTOL_SQUAT = 'movement-pistol-squat';
    public const string MOVEMENT_TURKISH_GET_UP = 'movement-turkish-get-up';
    public const string MOVEMENT_GHD_SIT_UP = 'movement-ghd-sit-up';
    public const string MOVEMENT_GHD_BACK_EXTENSION = 'movement-ghd-back-extension';
    public const string MOVEMENT_GHD_HIP_EXTENSION = 'movement-ghd-hip-extension';
    public const string MOVEMENT_CARRY = 'movement-carry';
    public const string MOVEMENT_SLED_DRAG = 'movement-sled-drag';
    public const string MOVEMENT_SLED_PUSH = 'movement-sled-push';
    public const string MOVEMENT_SLED_PULL = 'movement-sled-pull';
    public const string MOVEMENT_SIT_UP = 'movement-sit-up';
    public const string MOVEMENT_SHUTTLE_RUN = 'movement-shuttle-run';
    public const string MOVEMENT_BIKE = 'movement-bike';
    public const string MOVEMENT_RUN_AND_BIKE = 'movement-run-and-bike';
    public const string MOVEMENT_SWIM = 'movement-swim';
    public const string MOVEMENT_PADDLE = 'movement-paddle';
    public const string MOVEMENT_HIGH_BOX_JUMP = 'movement-high-box-jump';
    public const string MOVEMENT_HIGH_BOX_JUMP_OVER = 'movement-high-box-jump-over';
    public const string MOVEMENT_BOX_STEP_UP = 'movement-box-step-up';
    public const string MOVEMENT_WALL_WALK = 'movement-wall-walk';
    public const string MOVEMENT_DEFICIT_HANDSTAND_PUSH_UP = 'movement-deficit-handstand-push-up';
    public const string MOVEMENT_DEFICIT_STRICT_HANDSTAND_PUSH_UP = 'movement-deficit-strict_handstand-push-up';
    public const string MOVEMENT_BROAD_JUMP = 'movement-broad-jump';
    public const string MOVEMENT_BURPEE_BROAD_JUMP = 'movement-burpee-broad-jump';
    public const string MOVEMENT_PUSH_UP = 'movement-push-up';

    public function getDependencies(): array
    {
        return [
            MuscleData::class,
            ImplementData::class,
            MovementTypeData::class,
            MovementDifficultyData::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getMovements() as $movement) {
            $bodyParts = array_map(
                fn ($bodyPart) => $this->getReference($bodyPart, Muscle::class),
                $movement['muscles']
            );
            $possibleImplements = array_map(
                fn ($implement) => $this->getReference($implement, Implement::class),
                $movement['implements'] ?? []
            );
            $movementExecutionTimeForMeasureUnits = [];
            foreach ($movement['movementExecutionTimeForMeasureUnits'] as $measureUnit => $time) {
                $movementExecutionTimeForMeasureUnit = new MovementExecutionTimeForMeasureUnit(
                    MeasureUnitEnum::from($measureUnit),
                    $time
                );
                $manager->persist($movementExecutionTimeForMeasureUnit);
                $movementExecutionTimeForMeasureUnits[] = $movementExecutionTimeForMeasureUnit;
            }
            $manager->flush();
            $movementObject = new Movement(
                $movement['name'],
                $this->getReference($movement['difficulty'], MovementDifficulty::class),
                $this->getReference($movement['movementType'], MovementType::class),
            )
                ->setMuscles($bodyParts)
                ->setPossibleImplements($possibleImplements)
                ->setMovementExecutionTimeForMeasureUnits($movementExecutionTimeForMeasureUnits);
            $this->addReference($movement['reference'], $movementObject);
            $manager->persist($movementObject);
        }
        $manager->flush();
    }

    private function getMovements(): array
    {
        return [
            [
                'reference' => self::MOVEMENT_BENCH_PRESS,
                'name' => 'Bench Press',
                'muscles' => [
                    MuscleData::MUSCLE_PECTORALS,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_DELTOIDS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_BODYBUILDING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BENCH,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_INCLINE_BENCH_PRESS,
                'name' => 'Incline Bench Press',
                'muscles' => [
                    MuscleData::MUSCLE_PECTORALS,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_DELTOIDS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_BODYBUILDING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BENCH,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_PULL_UP,
                'name' => 'Pull Up',
                'muscles' => [
                    MuscleData::MUSCLE_LATISSIMUS_DORSI,
                    MuscleData::MUSCLE_BICEPS,
                    MuscleData::MUSCLE_FOREARMS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 1500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_DEADLIFT,
                'name' => 'Deadlift',
                'muscles' => [
                    MuscleData::MUSCLE_SPINAL_ERECTORS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                    MuscleData::MUSCLE_QUADRICEPS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_SAND_BAG,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_SHOULDER_PRESS,
                'name' => 'Shoulder Press',
                'muscles' => [
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_TRICEPS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                    ImplementData::IMPLEMENT_WORM,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2300,
                ],
            ],
            [
                'reference' => self::MOVEMENT_CHIN_UP,
                'name' => 'Chin Up',
                'muscles' => [
                    MuscleData::MUSCLE_LATISSIMUS_DORSI,
                    MuscleData::MUSCLE_BICEPS,
                    MuscleData::MUSCLE_FOREARMS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 3000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_CHEST_TO_BAR_PULL_UP,
                'name' => 'Chest to Bar Pull Up',
                'muscles' => [
                    MuscleData::MUSCLE_LATISSIMUS_DORSI,
                    MuscleData::MUSCLE_BICEPS,
                    MuscleData::MUSCLE_FOREARMS,
                    MuscleData::MUSCLE_RHOMBOIDS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_THRUSTER,
                'name' => 'Thruster',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_SPINAL_ERECTORS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                    ImplementData::IMPLEMENT_WORM,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_PUSH_PRESS,
                'name' => 'Push Press',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_CALVES,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                    ImplementData::IMPLEMENT_WORM,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 1700,
                ],
            ],
            [
                'reference' => self::MOVEMENT_PUSH_JERK,
                'name' => 'Push Jerk',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_CALVES,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                    ImplementData::IMPLEMENT_WORM,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_SQUAT_CLEAN,
                'name' => 'Squat Clean',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_TRAPEZIUS,
                    MuscleData::MUSCLE_SPINAL_ERECTORS,
                    MuscleData::MUSCLE_CALVES,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                    ImplementData::IMPLEMENT_WORM,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 3000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_POWER_CLEAN,
                'name' => 'Power Clean',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_TRAPEZIUS,
                    MuscleData::MUSCLE_SPINAL_ERECTORS,
                    MuscleData::MUSCLE_CALVES,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                    ImplementData::IMPLEMENT_WORM,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_CLEAN,
                'name' => 'Clean',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_TRAPEZIUS,
                    MuscleData::MUSCLE_SPINAL_ERECTORS,
                    MuscleData::MUSCLE_CALVES,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                    ImplementData::IMPLEMENT_WORM,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_HANG_POWER_CLEAN,
                'name' => 'Hang Power Clean',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_TRAPEZIUS,
                    MuscleData::MUSCLE_SPINAL_ERECTORS,
                    MuscleData::MUSCLE_CALVES,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                    ImplementData::IMPLEMENT_WORM,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 1200,
                ],
            ],
            [
                'reference' => self::MOVEMENT_HANG_SQUAT_CLEAN,
                'name' => 'Hang Squat Clean',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_TRAPEZIUS,
                    MuscleData::MUSCLE_SPINAL_ERECTORS,
                    MuscleData::MUSCLE_CALVES,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                    ImplementData::IMPLEMENT_WORM,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_LOW_HANG_POWER_CLEAN,
                'name' => 'Low Hang Power Clean',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_TRAPEZIUS,
                    MuscleData::MUSCLE_SPINAL_ERECTORS,
                    MuscleData::MUSCLE_CALVES,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_LOW_HANG_SQUAT_CLEAN,
                'name' => 'Low Hang Squat Clean',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_TRAPEZIUS,
                    MuscleData::MUSCLE_SPINAL_ERECTORS,
                    MuscleData::MUSCLE_CALVES,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 3500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_HIGH_HANG_POWER_CLEAN,
                'name' => 'High Hang Power Clean',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_TRAPEZIUS,
                    MuscleData::MUSCLE_SPINAL_ERECTORS,
                    MuscleData::MUSCLE_CALVES,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                    ImplementData::IMPLEMENT_WORM,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 1500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_HIGH_HANG_SQUAT_CLEAN,
                'name' => 'High Hang Squat Clean',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_TRAPEZIUS,
                    MuscleData::MUSCLE_SPINAL_ERECTORS,
                    MuscleData::MUSCLE_CALVES,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_SQUAT_SNATCH,
                'name' => 'Squat Snatch',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                    MuscleData::MUSCLE_TRAPEZIUS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_FOREARMS,
                    MuscleData::MUSCLE_CALVES,
                    MuscleData::MUSCLE_SPINAL_ERECTORS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 3000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_POWER_SNATCH,
                'name' => 'Power Snatch',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                    MuscleData::MUSCLE_TRAPEZIUS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_FOREARMS,
                    MuscleData::MUSCLE_CALVES,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_SNATCH,
                'name' => 'Snatch',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                    MuscleData::MUSCLE_TRAPEZIUS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_FOREARMS,
                    MuscleData::MUSCLE_CALVES,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_HANG_POWER_SNATCH,
                'name' => 'Hang Power Snatch',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                    MuscleData::MUSCLE_TRAPEZIUS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_FOREARMS,
                    MuscleData::MUSCLE_CALVES,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_HANG_SQUAT_SNATCH,
                'name' => 'Hang Squat Snatch',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                    MuscleData::MUSCLE_TRAPEZIUS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_FOREARMS,
                    MuscleData::MUSCLE_CALVES,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 3000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_MUSCLE_CLEAN,
                'name' => 'Muscle Clean',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_TRAPEZIUS,
                    MuscleData::MUSCLE_SPINAL_ERECTORS,
                    MuscleData::MUSCLE_CALVES,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                    ImplementData::IMPLEMENT_WORM,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_MUSCLE_SNATCH,
                'name' => 'Muscle Snatch',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_OVERHEAD_SQUAT,
                'name' => 'Overhead Squat',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_FOREARMS,
                    MuscleData::MUSCLE_SPINAL_ERECTORS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                    ImplementData::IMPLEMENT_WORM,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_FRONT_RACK_WALKING_LUNGE,
                'name' => 'Front Rack Walking Lunge',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 1500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_OVERHEAD_WALKING_LUNGE,
                'name' => 'Overhead Walking Lunge',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 1500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_BACK_RACK_WALKING_LUNGE,
                'name' => 'Back Rack Walking Lunge',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 1500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_SQUAT,
                'name' => 'Front Squat',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                    MuscleData::MUSCLE_SPINAL_ERECTORS,
                    MuscleData::MUSCLE_DELTOIDS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_BODYBUILDING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                    ImplementData::IMPLEMENT_SAND_BAG,
                    ImplementData::IMPLEMENT_WORM,
                    ImplementData::IMPLEMENT_HUSAFELL_BAG,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 1000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_FRONT_SQUAT,
                'name' => 'Front Squat',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                    MuscleData::MUSCLE_SPINAL_ERECTORS,
                    MuscleData::MUSCLE_DELTOIDS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_BODYBUILDING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                    ImplementData::IMPLEMENT_SAND_BAG,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_BACK_SQUAT,
                'name' => 'Back Squat',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_SPINAL_ERECTORS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_BODYBUILDING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                    ImplementData::IMPLEMENT_SAND_BAG,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_SINGLE_UNDER,
                'name' => 'Single Under',
                'muscles' => [
                    MuscleData::MUSCLE_CALVES,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_FOREARMS,
                    MuscleData::MUSCLE_PECTORALS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_CARDIO,
                'implements' => [
                    ImplementData::IMPLEMENT_JUMP_ROPE,
                    ImplementData::IMPLEMENT_HEAVY_JUMP_ROPE,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_DOUBLE_UNDER,
                'name' => 'Double Under',
                'muscles' => [
                    MuscleData::MUSCLE_CALVES,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_FOREARMS,
                    MuscleData::MUSCLE_PECTORALS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_CARDIO,
                'implements' => [
                    ImplementData::IMPLEMENT_JUMP_ROPE,
                    ImplementData::IMPLEMENT_HEAVY_JUMP_ROPE,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 700,
                ],
            ],
            [
                'reference' => self::MOVEMENT_CROSS_OVER,
                'name' => 'Cross Over',
                'muscles' => [
                    MuscleData::MUSCLE_CALVES,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_FOREARMS,
                    MuscleData::MUSCLE_PECTORALS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_CARDIO,
                'implements' => [
                    ImplementData::IMPLEMENT_JUMP_ROPE,
                    ImplementData::IMPLEMENT_HEAVY_JUMP_ROPE,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 700,
                ],
            ],
            [
                'reference' => self::MOVEMENT_BOX_JUMP_OVER,
                'name' => 'Box Jump Over',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_CALVES,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_PLYOMETRIC,
                'implements' => [
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_BOX_JUMP,
                'name' => 'Box Jump',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_CALVES,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_PLYOMETRIC,
                'implements' => [
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_WALL_BALL_SHOT,
                'name' => 'Wall Ball Shot',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_TRAPEZIUS,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_CARDIO,
                'implements' => [
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_AMERICAN_SWING,
                'name' => 'American Swing',
                'muscles' => [
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                    MuscleData::MUSCLE_FOREARMS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_RUSSIAN_SWING,
                'name' => 'Russian Swing',
                'muscles' => [
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                    MuscleData::MUSCLE_FOREARMS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 1500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_RUN,
                'name' => 'Run',
                'muscles' => [
                    MuscleData::MUSCLE_CALVES,
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_CARDIO,
                'implements' => [
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::METER->value => 370,
                ],
            ],
            [
                'reference' => self::MOVEMENT_ROW,
                'name' => 'Row',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                    MuscleData::MUSCLE_FOREARMS,
                    MuscleData::MUSCLE_LATISSIMUS_DORSI,
                    MuscleData::MUSCLE_BICEPS,
                    MuscleData::MUSCLE_RHOMBOIDS,
                    MuscleData::MUSCLE_TRAPEZIUS,
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                    MuscleData::MUSCLE_SPINAL_ERECTORS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_CARDIO,
                'implements' => [
                    ImplementData::IMPLEMENT_ROWER,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::METER->value => 240,
                    MeasureUnitEnum::CALORIE->value => 2000,
                    MeasureUnitEnum::KILOMETER->value => 240000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_BIKE_ERG,
                'name' => 'Bike Erg',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                    MuscleData::MUSCLE_CALVES,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_CARDIO,
                'implements' => [
                    ImplementData::IMPLEMENT_BIKE_ERG,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::METER->value => 240,
                    MeasureUnitEnum::CALORIE->value => 2700,
                    MeasureUnitEnum::KILOMETER->value => 240000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_ASSAULT_BIKE,
                'name' => 'Assault Bike',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                    MuscleData::MUSCLE_CALVES,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_RHOMBOIDS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_PECTORALS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_CARDIO,
                'implements' => [
                    ImplementData::IMPLEMENT_ASSAULT_BIKE,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::METER->value => 240,
                    MeasureUnitEnum::CALORIE->value => 2000,
                    MeasureUnitEnum::KILOMETER->value => 240000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_SKI_ERG,
                'name' => 'Ski Erg',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                    MuscleData::MUSCLE_LATISSIMUS_DORSI,
                    MuscleData::MUSCLE_BICEPS,
                    MuscleData::MUSCLE_TRICEPS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_CARDIO,
                'implements' => [
                    ImplementData::IMPLEMENT_SKI_ERG,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::METER->value => 300,
                    MeasureUnitEnum::CALORIE->value => 2300,
                    MeasureUnitEnum::KILOMETER->value => 300000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_BURPEE,
                'name' => 'Burpee',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                    MuscleData::MUSCLE_LATISSIMUS_DORSI,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_PECTORALS,
                    MuscleData::MUSCLE_CALVES,
                    MuscleData::MUSCLE_HAMSTRINGS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_CARDIO,
                'implements' => [
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 3000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_BURPEE_BOX_JUMP_OVER,
                'name' => 'Burpee Box Jump Over',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                    MuscleData::MUSCLE_LATISSIMUS_DORSI,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_PECTORALS,
                    MuscleData::MUSCLE_CALVES,
                    MuscleData::MUSCLE_HAMSTRINGS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_CARDIO,
                'implements' => [
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 4000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_BURPEE_OVER,
                'name' => 'Burpee Over',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                    MuscleData::MUSCLE_LATISSIMUS_DORSI,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_PECTORALS,
                    MuscleData::MUSCLE_CALVES,
                    MuscleData::MUSCLE_HAMSTRINGS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_CARDIO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                    ImplementData::IMPLEMENT_LINE,
                    ImplementData::IMPLEMENT_WORM,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 4000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_BURPEE_OVER_FACING,
                'name' => 'Burpee Over Facing',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                    MuscleData::MUSCLE_LATISSIMUS_DORSI,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_PECTORALS,
                    MuscleData::MUSCLE_CALVES,
                    MuscleData::MUSCLE_HAMSTRINGS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_CARDIO,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                    ImplementData::IMPLEMENT_LINE,
                    ImplementData::IMPLEMENT_WORM,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 3500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_BURPEE_PULL_UP,
                'name' => 'Burpee Pull Up',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                    MuscleData::MUSCLE_LATISSIMUS_DORSI,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_PECTORALS,
                    MuscleData::MUSCLE_CALVES,
                    MuscleData::MUSCLE_HAMSTRINGS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_CARDIO,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 5000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_BURPEE_MUSCLE_UP,
                'name' => 'Burpee Muscle Up',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                    MuscleData::MUSCLE_LATISSIMUS_DORSI,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_PECTORALS,
                    MuscleData::MUSCLE_CALVES,
                    MuscleData::MUSCLE_HAMSTRINGS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 6000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_BURPEE_CHEST_TO_BAR_PULL_UP,
                'name' => 'Burpee Chest to Bar Pull Up',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                    MuscleData::MUSCLE_LATISSIMUS_DORSI,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_PECTORALS,
                    MuscleData::MUSCLE_CALVES,
                    MuscleData::MUSCLE_HAMSTRINGS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 4500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_STRICT_CHEST_TO_BAR_PULL_UP,
                'name' => 'Strict Chest to Bar Pull Up',
                'muscles' => [
                    MuscleData::MUSCLE_LATISSIMUS_DORSI,
                    MuscleData::MUSCLE_BICEPS,
                    MuscleData::MUSCLE_FOREARMS,
                    MuscleData::MUSCLE_RHOMBOIDS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_RX,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 3000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_STRICT_PULL_UP,
                'name' => 'Strict Pull Up',
                'muscles' => [
                    MuscleData::MUSCLE_LATISSIMUS_DORSI,
                    MuscleData::MUSCLE_BICEPS,
                    MuscleData::MUSCLE_FOREARMS,
                    MuscleData::MUSCLE_RHOMBOIDS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_MUSCLE_UP,
                'name' => 'Muscle Up',
                'muscles' => [
                    MuscleData::MUSCLE_LATISSIMUS_DORSI,
                    MuscleData::MUSCLE_BICEPS,
                    MuscleData::MUSCLE_FOREARMS,
                    MuscleData::MUSCLE_RHOMBOIDS,
                    MuscleData::MUSCLE_PECTORALS,
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 3000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_PULL_OVER,
                'name' => 'Pull Over',
                'muscles' => [
                    MuscleData::MUSCLE_LATISSIMUS_DORSI,
                    MuscleData::MUSCLE_BICEPS,
                    MuscleData::MUSCLE_FOREARMS,
                    MuscleData::MUSCLE_RHOMBOIDS,
                    MuscleData::MUSCLE_PECTORALS,
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_RX,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 3000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_DIP,
                'name' => 'Dip',
                'muscles' => [
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_PECTORALS,
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                    MuscleData::MUSCLE_DELTOIDS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                    ImplementData::IMPLEMENT_RINGS,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_HANDSTAND_PUSH_UP,
                'name' => 'Handstand Push Up',
                'muscles' => [
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_PECTORALS,
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_BURPEE_TARGET,
                'name' => 'Burpee Target',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_PECTORALS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_CARDIO,
                'implements' => [
                    ImplementData::IMPLEMENT_RINGS,
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 3500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_BURPEE_PULL_OVER,
                'name' => 'Burpee Pull Over',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_PECTORALS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                    MuscleData::MUSCLE_FOREARMS,
                    MuscleData::MUSCLE_RHOMBOIDS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_RX,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 6500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_HANDSTAND_PIROUETTE,
                'name' => 'Handstand Pirouette',
                'muscles' => [
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_PECTORALS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_RX,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 5000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_HANDSTAND_WALK,
                'name' => 'Handstand Walk',
                'muscles' => [
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_PECTORALS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::METER->value => 1500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_STRICT_HANDSTAND_PUSH_UP,
                'name' => 'Strict Handstand Push Up',
                'muscles' => [
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_PECTORALS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_RX,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_DEFICIT_HANDSTAND_PUSH_UP,
                'name' => 'Deficit Handstand Push Up',
                'muscles' => [
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_PECTORALS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_RX,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                    ImplementData::IMPLEMENT_PARALLETTE,
                    ImplementData::IMPLEMENT_PLATE,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 3000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_DEFICIT_STRICT_HANDSTAND_PUSH_UP,
                'name' => 'Deficit Strict Handstand Push Up',
                'muscles' => [
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_PECTORALS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_ELITE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                    ImplementData::IMPLEMENT_PARALLETTE,
                    ImplementData::IMPLEMENT_PLATE,
                    ImplementData::IMPLEMENT_RINGS,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 4000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_WALL_FACING_HANDSTAND_PUSH_UP,
                'name' => 'Wall Facing Handstand Push Up',
                'muscles' => [
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_PECTORALS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_RX,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_WALL_FACING_STRICT_HANDSTAND_PUSH_UP,
                'name' => 'Wall Facing Handstand Push Up',
                'muscles' => [
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_PECTORALS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_RX,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_WALL_FACING_DEFICIT_HANDSTAND_PUSH_UP,
                'name' => 'Wall Facing Deficit Handstand Push Up',
                'muscles' => [
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_PECTORALS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_ELITE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                    ImplementData::IMPLEMENT_PARALLETTE,
                    ImplementData::IMPLEMENT_PLATE,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 4000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_WALL_FACING_DEFICIT_STRICT_HANDSTAND_PUSH_UP,
                'name' => 'Wall facing Deficit Strict Handstand Push Up',
                'muscles' => [
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_PECTORALS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_ELITE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                    ImplementData::IMPLEMENT_PARALLETTE,
                    ImplementData::IMPLEMENT_PLATE,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 4000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_TOES_TO_BAR,
                'name' => 'Toes to Bar',
                'muscles' => [
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                    MuscleData::MUSCLE_LATISSIMUS_DORSI,
                    MuscleData::MUSCLE_BICEPS,
                    MuscleData::MUSCLE_FOREARMS,
                    MuscleData::MUSCLE_HIP_FLEXORS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_TOES_TO_RING,
                'name' => 'Toes to Ring',
                'muscles' => [
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                    MuscleData::MUSCLE_LATISSIMUS_DORSI,
                    MuscleData::MUSCLE_BICEPS,
                    MuscleData::MUSCLE_FOREARMS,
                    MuscleData::MUSCLE_HIP_FLEXORS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                    ImplementData::IMPLEMENT_RINGS,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_STRICT_TOES_TO_BAR,
                'name' => 'Strict Toes to Bar',
                'muscles' => [
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                    MuscleData::MUSCLE_LATISSIMUS_DORSI,
                    MuscleData::MUSCLE_BICEPS,
                    MuscleData::MUSCLE_FOREARMS,
                    MuscleData::MUSCLE_HIP_FLEXORS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 4000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_KNEES_TO_ELBOWS,
                'name' => 'Knees to Elbows',
                'muscles' => [
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                    MuscleData::MUSCLE_LATISSIMUS_DORSI,
                    MuscleData::MUSCLE_BICEPS,
                    MuscleData::MUSCLE_FOREARMS,
                    MuscleData::MUSCLE_HIP_FLEXORS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_KNEES_RAISE,
                'name' => 'Knees raise',
                'muscles' => [
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                    MuscleData::MUSCLE_LATISSIMUS_DORSI,
                    MuscleData::MUSCLE_BICEPS,
                    MuscleData::MUSCLE_FOREARMS,
                    MuscleData::MUSCLE_HIP_FLEXORS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                    ImplementData::IMPLEMENT_PULL_UP_BAR,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_ALTERNATE_PISTOL_SQUAT,
                'name' => 'Alternate Pistol Squat',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 3000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_PISTOL_SQUAT,
                'name' => 'Pistol Squat',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 3000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_TURKISH_GET_UP,
                'name' => 'Turkish Get Up',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_PECTORALS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_WEIGHTLIFTING,
                'implements' => [
                    ImplementData::IMPLEMENT_KETTLEBELL,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 10000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_GHD_SIT_UP,
                'name' => 'GHD Sit Up',
                'muscles' => [
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                    MuscleData::MUSCLE_HIP_FLEXORS,
                    MuscleData::MUSCLE_QUADRICEPS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                    ImplementData::IMPLEMENT_GHD,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_GHD_BACK_EXTENSION,
                'name' => 'GHD Back Extension',
                'muscles' => [
                    MuscleData::MUSCLE_HAMSTRINGS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_SPINAL_ERECTORS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                    ImplementData::IMPLEMENT_GHD,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_GHD_HIP_EXTENSION,
                'name' => 'GHD Hip Extension',
                'muscles' => [
                    MuscleData::MUSCLE_HAMSTRINGS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_SPINAL_ERECTORS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                    ImplementData::IMPLEMENT_GHD,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 2000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_CARRY,
                'name' => 'Carry',
                'muscles' => [
                    MuscleData::MUSCLE_FOREARMS,
                    MuscleData::MUSCLE_BICEPS,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_TRAPEZIUS,
                    MuscleData::MUSCLE_CALVES,
                    MuscleData::MUSCLE_QUADRICEPS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_STRONGMAN,
                'implements' => [
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                    ImplementData::IMPLEMENT_SAND_BAG,
                    ImplementData::IMPLEMENT_HUSAFELL_BAG,
                    ImplementData::IMPLEMENT_WORM,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::METER->value => 700,
                ],
            ],
            [
                'reference' => self::MOVEMENT_SLED_DRAG,
                'name' => 'Sled Drag',
                'muscles' => [
                    MuscleData::MUSCLE_FOREARMS,
                    MuscleData::MUSCLE_BICEPS,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_TRAPEZIUS,
                    MuscleData::MUSCLE_CALVES,
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_STRONGMAN,
                'implements' => [
                    ImplementData::IMPLEMENT_SLED,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 1000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_SLED_PUSH,
                'name' => 'Sled Push',
                'muscles' => [
                    MuscleData::MUSCLE_FOREARMS,
                    MuscleData::MUSCLE_BICEPS,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_TRAPEZIUS,
                    MuscleData::MUSCLE_CALVES,
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_STRONGMAN,
                'implements' => [
                    ImplementData::IMPLEMENT_SLED,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 700,
                ],
            ],
            [
                'reference' => self::MOVEMENT_SLED_PULL,
                'name' => 'Sled Pull',
                'muscles' => [
                    MuscleData::MUSCLE_FOREARMS,
                    MuscleData::MUSCLE_BICEPS,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_TRAPEZIUS,
                    MuscleData::MUSCLE_CALVES,
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_STRONGMAN,
                'implements' => [
                    ImplementData::IMPLEMENT_SLED,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 1000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_SIT_UP,
                'name' => 'Sit Up',
                'muscles' => [
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                    MuscleData::MUSCLE_HIP_FLEXORS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 3000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_SHUTTLE_RUN,
                'name' => 'Shuttle Run',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_CALVES,
                    MuscleData::MUSCLE_HAMSTRINGS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_SPINAL_ERECTORS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_CARDIO,
                'implements' => [
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 10000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_BIKE,
                'name' => 'Bike',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_CALVES,
                    MuscleData::MUSCLE_HAMSTRINGS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_SPINAL_ERECTORS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_CARDIO,
                'implements' => [
                    ImplementData::IMPLEMENT_BIKE,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::METER->value => 200,
                    MeasureUnitEnum::KILOMETER->value => 200000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_RUN_AND_BIKE,
                'name' => 'Run and Bike',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_CALVES,
                    MuscleData::MUSCLE_HAMSTRINGS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_SPINAL_ERECTORS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_CARDIO,
                'implements' => [
                    ImplementData::IMPLEMENT_BIKE,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::METER->value => 370,
                    MeasureUnitEnum::KILOMETER->value => 370000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_SWIM,
                'name' => 'Swim',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                    MuscleData::MUSCLE_SPINAL_ERECTORS,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_PECTORALS,
                    MuscleData::MUSCLE_LATISSIMUS_DORSI,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_CARDIO,
                'implements' => [
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::METER->value => 1200,
                    MeasureUnitEnum::KILOMETER->value => 1200000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_PADDLE,
                'name' => 'Paddle',
                'muscles' => [
                    MuscleData::MUSCLE_SPINAL_ERECTORS,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_PECTORALS,
                    MuscleData::MUSCLE_TRAPEZIUS,
                    MuscleData::MUSCLE_LATISSIMUS_DORSI,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_CARDIO,
                'implements' => [
                    ImplementData::IMPLEMENT_PADDLE,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 1000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_HIGH_BOX_JUMP,
                'name' => 'High Box Jump',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_CALVES,
                    MuscleData::MUSCLE_HAMSTRINGS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_PLYOMETRIC,
                'implements' => [
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 3500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_HIGH_BOX_JUMP_OVER,
                'name' => 'High Box Jump Over',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_CALVES,
                    MuscleData::MUSCLE_HAMSTRINGS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_PLYOMETRIC,
                'implements' => [
                    ImplementData::IMPLEMENT_BOX,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 3500,
                ],
            ],
            [
                'reference' => self::MOVEMENT_BOX_STEP_UP,
                'name' => 'Box Step Up',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                    MuscleData::MUSCLE_HAMSTRINGS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_PLYOMETRIC,
                'implements' => [
                    ImplementData::IMPLEMENT_BOX,
                    ImplementData::IMPLEMENT_KETTLEBELL,
                    ImplementData::IMPLEMENT_DOUBLE_KETTLEBELLS,
                    ImplementData::IMPLEMENT_DUMBBELL,
                    ImplementData::IMPLEMENT_DOUBLE_DUMBBELLS,
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_MEDICINE_BALL,
                    ImplementData::IMPLEMENT_SAND_BAG,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 3000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_WALL_WALK,
                'name' => 'Wall Walk',
                'muscles' => [
                    MuscleData::MUSCLE_DELTOIDS,
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_PECTORALS,
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                    MuscleData::MUSCLE_TRAPEZIUS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_INTERMEDIATE,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                    ImplementData::IMPLEMENT_LINE,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 5000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_BROAD_JUMP,
                'name' => 'Broad Jump',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_PLYOMETRIC,
                'implements' => [
                    ImplementData::IMPLEMENT_BAND,
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 1000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_BURPEE_BROAD_JUMP,
                'name' => 'Burpee Broad Jump',
                'muscles' => [
                    MuscleData::MUSCLE_QUADRICEPS,
                    MuscleData::MUSCLE_GLUTEUS_MAXIMUS,
                    MuscleData::MUSCLE_GLUTEUS_MEDIUS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_PLYOMETRIC,
                'implements' => [
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 4000,
                ],
            ],
            [
                'reference' => self::MOVEMENT_PUSH_UP,
                'name' => 'Push Up',
                'muscles' => [
                    MuscleData::MUSCLE_TRICEPS,
                    MuscleData::MUSCLE_PECTORALS,
                    MuscleData::MUSCLE_RECTUS_ABDOMINIS,
                    MuscleData::MUSCLE_OBLIQUES,
                    MuscleData::MUSCLE_TRANSVERSUS_ABDOMINIS,
                    MuscleData::MUSCLE_DELTOIDS,
                ],
                'difficulty' => MovementDifficultyData::MOVEMENT_DIFFICULTY_BEGINNER,
                'movementType' => MovementTypeData::MOVEMENT_TYPE_GYMNASTIC,
                'implements' => [
                ],
                'movementExecutionTimeForMeasureUnits' => [
                    MeasureUnitEnum::REPETITION->value => 1500,
                ],
            ],
        ];
    }
}
