<?php

namespace App\DataFixtures;

use App\Entity\Workout\Movement;
use App\Enum\MovementType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class MovementData extends Fixture implements DependentFixtureInterface
{
    public const MOVEMENT_BENCH_PRESS = 'movement-bench-press';
    public const MOVEMENT_INCLINE_BENCH_PRESS = 'movement-incline-bench-press';
    public const MOVEMENT_PULL_UP = 'movement-pull-up';
    public const MOVEMENT_DEADLIFT = 'movement-deadlift';
    public const MOVEMENT_SHOULDER_PRESS = 'movement-shoulder-press';
    public const MOVEMENT_CHIN_UP = 'movement-chin-up';
    public const MOVEMENT_CHEST_TO_BAR_PULL_UP = 'movement-chest-to-bar-pull-up';
    public const MOVEMENT_THRUSTER = 'movement-thruster';
    public const MOVEMENT_PUSH_PRESS = 'movement-push-press';
    public const MOVEMENT_PUSH_JERK = 'movement-push-jerk';
    public const MOVEMENT_SQUAT_CLEAN = 'movement-squat-clean';
    public const MOVEMENT_POWER_CLEAN = 'movement-power-clean';
    public const MOVEMENT_CLEAN = 'movement-clean';
    public const MOVEMENT_HANG_POWER_CLEAN = 'movement-hang-power-clean';
    public const MOVEMENT_HANG_SQUAT_CLEAN = 'movement-hang-squat-clean';
    public const MOVEMENT_LOW_HANG_POWER_CLEAN = 'movement-low-hang-power-clean';
    public const MOVEMENT_LOW_HANG_SQUAT_CLEAN = 'movement-low-hang-squat-clean';
    public const MOVEMENT_HIGH_HANG_POWER_CLEAN = 'movement-high-hang-power-clean';
    public const MOVEMENT_HIGH_HANG_SQUAT_CLEAN = 'movement-high-hang-squat-clean';
    public const MOVEMENT_SQUAT_SNATCH = 'movement-squat-snatch';
    public const MOVEMENT_POWER_SNATCH = 'movement-power-snatch';
    public const MOVEMENT_SNATCH = 'movement-snatch';
    public const MOVEMENT_HANG_POWER_SNATCH = 'movement-hang-snatch';
    public const MOVEMENT_HANG_SQUAT_SNATCH = 'movement-hang-squat-snatch';
    public const MOVEMENT_MUSCLE_CLEAN = 'movement-muscle-clean';
    public const MOVEMENT_MUSCLE_SNATCH = 'movement-muscle-snatch';
    public const MOVEMENT_OVERHEAD_SQUAT = 'movement-overhead-squat';
    public const MOVEMENT_FRONT_RACK_WALKING_LUNGE = 'movement-front-rack-walking-lunge';
    public const MOVEMENT_OVERHEAD_WALKING_LUNGE = 'movement-overhead-walking-lunge';
    public const MOVEMENT_BACK_RACK_WALKING_LUNGE = 'movement-back-rack-walking-lunge';
    public const MOVEMENT_FRONT_SQUAT = 'movement-front-squat';
    public const MOVEMENT_BACK_SQUAT = 'movement-back-squat';
    public const MOVEMENT_SINGLE_UNDER = 'movement-single-under';
    public const MOVEMENT_DOUBLE_UNDER = 'movement-double-under';
    public const MOVEMENT_CROSS_OVER = 'movement-cross-over';
    public const MOVEMENT_BOX_JUMP_OVER = 'movement-box-jump-over';
    public const MOVEMENT_BOX_JUMP = 'movement-box-jump';
    public const MOVEMENT_WALL_BALL_SHOT = 'movement-wall-ball-shot';
    public const MOVEMENT_AMERICAN_SWING = 'movement-american_swing';
    public const MOVEMENT_RUSSIAN_SWING = 'movement-russian_swing';
    public const MOVEMENT_RUN = 'movement-run';
    public const MOVEMENT_ROW = 'movement-row';
    public const MOVEMENT_BIKE_ERG = 'movement-bike-erg';
    public const MOVEMENT_ASSAULT_BIKE = 'movement-assault-bike';
    public const MOVEMENT_SKI_ERG = 'movement-ski-erg';
    public const MOVEMENT_BURPEE = 'movement-burpee';
    public const MOVEMENT_BURPEE_BOX_JUMP_OVER = 'movement-burpee-box-jump-over';
    public const MOVEMENT_BURPEE_OVER = 'movement-burpee-over';
    public const MOVEMENT_BURPEE_OVER_FACING = 'movement-burpee-over-facing';
    public const MOVEMENT_BURPEE_PULL_UP = 'movement-burpee-pull-up';
    public const MOVEMENT_BURPEE_MUSCLE_UP = 'movement-burpee-muscle-up';
    public const MOVEMENT_BURPEE_CHEST_TO_BAR_PULL_UP = 'movement-burpee-chest-to-bar-pull-up';
    public const MOVEMENT_STRICT_CHEST_TO_BAR_PULL_UP = 'movement-strict_chest-to-bar-pull-up';
    public const MOVEMENT_STRICT_PULL_UP = 'movement-strict-pull-up';
    public const MOVEMENT_MUSCLE_UP = 'movement-muscle-up';
    public const MOVEMENT_PULL_OVER = 'movement-pull-over';
    public const MOVEMENT_DIP = 'movement-dip';
    public const MOVEMENT_HANDSTAND_PUSH_UP = 'movement-handstand-push-up';
    public const MOVEMENT_BURPEE_TARGET = 'movement-burpee-target';
    public const MOVEMENT_BURPEE_PULL_OVER = 'movement-burpee-pull-over';
    public const MOVEMENT_WALL_FACING_HANDSTAND_PUSH_UP = 'movement-wall-facing-handstand-push-up';
    public const MOVEMENT_HANDSTAND_PIROUETTE = 'movement-handstand-pirouette';
    public const MOVEMENT_HANDSTAND_WALK = 'movement-handstand-walk';
    public const MOVEMENT_STRICT_HANDSTAND_PUSH_UP = 'movement-strict-handstand-push-up';
    public const MOVEMENT_KIPPING_HANDSTAND_PUSH_UP = 'movement-kipping-handstand-push-up';
    public const MOVEMENT_TOES_TO_BAR = 'movement-toes-to-bar';
    public const MOVEMENT_TOES_TO_RING = 'movement-toes-to-ring';
    public const MOVEMENT_STRICT_TOES_TO_BAR = 'movement-strict-toes-to-bar';
    public const MOVEMENT_KNEES_TO_ELBOWS = 'movement-knees-to-elbows';
    public const MOVEMENT_ALTERNATE_PISTOL_SQUAT = 'movement-alternate-pistol-squat';
    public const MOVEMENT_PISTOL_SQUAT = 'movement-pistol-squat';
    public const MOVEMENT_TURKISH_GET_UP = 'movement-turkish-get-up';
    public const MOVEMENT_GHD_SIT_UP = 'movement-ghd-sit-up';
    public const MOVEMENT_GHD_BACK_EXTENSION = 'movement-ghd-back-extension';
    public const MOVEMENT_GHD_HIP_EXTENSION = 'movement-ghd-hip-extension';
    public const MOVEMENT_CARRY = 'movement-carry';
    public const MOVEMENT_SLED_DRAG = 'movement-sled-drag';
    public const MOVEMENT_SLED_PUSH = 'movement-sled-push';
    public const MOVEMENT_SLED_PULL = 'movement-sled-pull';
    public const MOVEMENT_SIT_UP = 'movement-sit-up';
    public const MOVEMENT_SHUTTLE_RUN = 'movement-shuttle-run';
    public const MOVEMENT_BIKE = 'movement-bike';
    public const MOVEMENT_RUN_AND_BIKE = 'movement-run-and-bike';
    public const MOVEMENT_SWIM = 'movement-swim';
    public const MOVEMENT_PADDLE = 'movement-paddle';
    public const MOVEMENT_HIGH_BOX_JUMP = 'movement-high-box-jump';
    public const MOVEMENT_HIGH_BOX_JUMP_OVER = 'movement-high-box-jump-over';
    public const MOVEMENT_BOX_STEP_UP = 'movement-box-step-up';
    public const MOVEMENT_WALL_WALK = 'movement-wall-walk';
    public const MOVEMENT_DEFICIT_HANDSTAND_PUSH_UP = 'movement-deficit-handstand-push-up';
    public const MOVEMENT_BROAD_JUMP = 'movement-broad-jump';
    public const MOVEMENT_BURPEE_BROAD_JUMP = 'movement-burpee-broad-jump';
    public const MOVEMENT_PUSH_UP = 'movement-push-up';


    public function getDependencies(): array
    {
        return [
            BodyPartData::class,
            ImplementData::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getMovements() as $movement) {
            $bodyParts = array_map(
                fn ($bodyPart) => $this->getReference($bodyPart),
                $movement['bodyParts']
            );
            $movement = (new Movement(
                $movement['name'],
                $movement['difficulty'],
                $movement['movementType'],
            ))
                ->setBodyparts($bodyParts);
            $this->addReference($movement['reference'], $movement);
            $manager->persist($movement);
        }
        $manager->flush();
    }

    private function getMovements():array
    {
        return [
            [
                'reference' => self::MOVEMENT_BENCH_PRESS,
                'name' => 'Bench Press',
                'bodyParts' => [
                    BodyPartData::BODY_PART_PECTORALS,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_SHOULDERS,
                ],
                'difficulty' => 10,
                'movementType' => MovementType::BODYBUILDING,
                'implements' => [
                    ImplementData::IMPLEMENT_BARBELL,
                    ImplementData::IMPLEMENT_BENCH,
                ],
            ],
            [
                'reference' => self::MOVEMENT_INCLINE_BENCH_PRESS,
                'name' => 'Incline Bench Press',
                'bodyParts' => [
                    BodyPartData::BODY_PART_PECTORALS,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_SHOULDERS,
                ],
                'difficulty' => 10,
                'movementType' => MovementType::BODYBUILDING,
            ],
            [
                'reference' => self::MOVEMENT_PULL_UP,
                'name' => 'Pull Up',
                'bodyParts' => [
                    BodyPartData::BODY_PART_LATISSIMUS_DORSI,
                    BodyPartData::BODY_PART_BICEPS,
                    BodyPartData::BODY_PART_FOREARMS,
                ],
                'difficulty' => 50,
                'movementType' => MovementType::GYMNASTIC,
                'implements' => [
                    ImplementData::PULL_UP_BAR,
                ],
            ],
            [
                'reference' => self::MOVEMENT_DEADLIFT,
                'name' => 'Deadlift',
                'bodyParts' => [
                    BodyPartData::BODY_PART_LOWER_BACK,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                    BodyPartData::BODY_PART_QUADRICEPS,
                ],
                'difficulty' => 10,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_SHOULDER_PRESS,
                'name' => 'Shoulder Press',
                'bodyParts' => [
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_TRICEPS,
                ],
                'difficulty' => 10,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_CHIN_UP,
                'name' => 'Chin Up',
                'bodyParts' => [
                    BodyPartData::BODY_PART_LATISSIMUS_DORSI,
                    BodyPartData::BODY_PART_BICEPS,
                    BodyPartData::BODY_PART_FOREARMS,
                ],
                'difficulty' => 50,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_CHEST_TO_BAR_PULL_UP,
                'name' => 'Chest to Bar Pull Up',
                'bodyParts' => [
                    BodyPartData::BODY_PART_LATISSIMUS_DORSI,
                    BodyPartData::BODY_PART_BICEPS,
                    BodyPartData::BODY_PART_FOREARMS,
                    BodyPartData::BODY_PART_RHOMBOIDS,
                ],
                'difficulty' => 60,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_THRUSTER,
                'name' => 'Thruster',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_LOWER_BACK,
                ],
                'difficulty' => 30,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_PUSH_PRESS,
                'name' => 'Push Press',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_CALVES,
                ],
                'difficulty' => 20,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_PUSH_JERK,
                'name' => 'Push Jerk',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_CALVES,
                ],
                'difficulty' => 30,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_SQUAT_CLEAN,
                'name' => 'Squat Clean',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_TRAPEZIUS,
                    BodyPartData::BODY_PART_LOWER_BACK,
                    BodyPartData::BODY_PART_CALVES,
                ],
                'difficulty' => 40,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_POWER_CLEAN,
                'name' => 'Power Clean',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_TRAPEZIUS,
                    BodyPartData::BODY_PART_LOWER_BACK,
                    BodyPartData::BODY_PART_CALVES,
                ],
                'difficulty' => 30,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_CLEAN,
                'name' => 'Clean',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_TRAPEZIUS,
                    BodyPartData::BODY_PART_LOWER_BACK,
                    BodyPartData::BODY_PART_CALVES,
                ],
                'difficulty' => 35,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_HANG_POWER_CLEAN,
                'name' => 'Hang Power Clean',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_TRAPEZIUS,
                    BodyPartData::BODY_PART_LOWER_BACK,
                    BodyPartData::BODY_PART_CALVES,
                ],
                'difficulty' => 35,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_HANG_SQUAT_CLEAN,
                'name' => 'Hang Squat Clean',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_TRAPEZIUS,
                    BodyPartData::BODY_PART_LOWER_BACK,
                    BodyPartData::BODY_PART_CALVES,
                ],
                'difficulty' => 45,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_LOW_HANG_POWER_CLEAN,
                'name' => 'Low Hang Power Clean',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_TRAPEZIUS,
                    BodyPartData::BODY_PART_LOWER_BACK,
                    BodyPartData::BODY_PART_CALVES,
                ],
                'difficulty' => 35,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_LOW_HANG_SQUAT_CLEAN,
                'name' => 'Low Hang Squat Clean',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_TRAPEZIUS,
                    BodyPartData::BODY_PART_LOWER_BACK,
                    BodyPartData::BODY_PART_CALVES,
                ],
                'difficulty' => 45,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_HIGH_HANG_POWER_CLEAN,
                'name' => 'High Hang Power Clean',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_TRAPEZIUS,
                    BodyPartData::BODY_PART_LOWER_BACK,
                    BodyPartData::BODY_PART_CALVES,
                ],
                'difficulty' => 35,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_HIGH_HANG_SQUAT_CLEAN,
                'name' => 'High Hang Squat Clean',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_TRAPEZIUS,
                    BodyPartData::BODY_PART_LOWER_BACK,
                    BodyPartData::BODY_PART_CALVES,
                ],
                'difficulty' => 45,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_SQUAT_SNATCH,
                'name' => 'Squat Snatch',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                    BodyPartData::BODY_PART_TRAPEZIUS,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_FOREARMS,
                    BodyPartData::BODY_PART_CALVES,
                    BodyPartData::BODY_PART_LOWER_BACK,
                ],
                'difficulty' => 60,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_POWER_SNATCH,
                'name' => 'Power Snatch',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                    BodyPartData::BODY_PART_TRAPEZIUS,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_FOREARMS,
                    BodyPartData::BODY_PART_CALVES,
                ],
                'difficulty' => 50,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_SNATCH,
                'name' => 'Snatch',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                    BodyPartData::BODY_PART_TRAPEZIUS,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_FOREARMS,
                    BodyPartData::BODY_PART_CALVES,
                ],
                'difficulty' => 55,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_HANG_POWER_SNATCH,
                'name' => 'Hang Power Snatch',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                    BodyPartData::BODY_PART_TRAPEZIUS,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_FOREARMS,
                    BodyPartData::BODY_PART_CALVES,
                ],
                'difficulty' => 55,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_HANG_SQUAT_SNATCH,
                'name' => 'Hang Squat Snatch',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                    BodyPartData::BODY_PART_TRAPEZIUS,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_FOREARMS,
                    BodyPartData::BODY_PART_CALVES,
                ],
                'difficulty' => 65,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_MUSCLE_CLEAN,
                'name' => 'Muscle Clean',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_TRAPEZIUS,
                    BodyPartData::BODY_PART_LOWER_BACK,
                    BodyPartData::BODY_PART_CALVES,
                ],
                'difficulty' => 30,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_MUSCLE_SNATCH,
                'name' => 'Muscle Snatch',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                ],
                'difficulty' => 30,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_OVERHEAD_SQUAT,
                'name' => 'Overhead Squat',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_FOREARMS,
                    BodyPartData::BODY_PART_LOWER_BACK,
                ],
                'difficulty' => 60,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_FRONT_RACK_WALKING_LUNGE,
                'name' => 'Front Rack Walking Lunge',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_ABDOMINALS,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                ],
                'difficulty' => 30,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_OVERHEAD_WALKING_LUNGE,
                'name' => 'Overhead Walking Lunge',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_ABDOMINALS,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                ],
                'difficulty' => 30,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_BACK_RACK_WALKING_LUNGE,
                'name' => 'Back Rack Walking Lunge',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_ABDOMINALS,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                ],
                'difficulty' => 30,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_FRONT_SQUAT,
                'name' => 'Front Squat',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                    BodyPartData::BODY_PART_LOWER_BACK,
                    BodyPartData::BODY_PART_SHOULDERS,
                ],
                'difficulty' => 20,
                'movementType' => MovementType::BODYBUILDING,
            ],
            [
                'reference' => self::MOVEMENT_BACK_SQUAT,
                'name' => 'Back Squat',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_LOWER_BACK,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                ],
                'difficulty' => 20,
                'movementType' => MovementType::BODYBUILDING,
            ],
            [
                'reference' => self::MOVEMENT_SINGLE_UNDER,
                'name' => 'Single Under',
                'bodyParts' => [
                    BodyPartData::BODY_PART_CALVES,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_FOREARMS,
                    BodyPartData::BODY_PART_PECTORALS,
                ],
                'difficulty' => 30,
                'movementType' => MovementType::CARDIO,
            ],
            [
                'reference' => self::MOVEMENT_DOUBLE_UNDER,
                'name' => 'Double Under',
                'bodyParts' => [
                    BodyPartData::BODY_PART_CALVES,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_FOREARMS,
                    BodyPartData::BODY_PART_PECTORALS,
                ],
                'difficulty' => 60,
                'movementType' => MovementType::CARDIO,
            ],
            [
                'reference' => self::MOVEMENT_CROSS_OVER,
                'name' => 'Cross Over',
                'bodyParts' => [
                    BodyPartData::BODY_PART_CALVES,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_FOREARMS,
                    BodyPartData::BODY_PART_PECTORALS,
                ],
                'difficulty' => 70,
                'movementType' => MovementType::CARDIO,
            ],
            [
                'reference' => self::MOVEMENT_BOX_JUMP_OVER,
                'name' => 'Box Jump Over',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_CALVES,
                ],
                'difficulty' => 30,
                'movementType' => MovementType::PLYOMETRIC,
            ],
            [
                'reference' => self::MOVEMENT_BOX_JUMP,
                'name' => 'Box Jump',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_CALVES,
                ],
                'difficulty' => 20,
                'movementType' => MovementType::PLYOMETRIC,
            ],
            [
                'reference' => self::MOVEMENT_WALL_BALL_SHOT,
                'name' => 'Wall Ball Shot',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_TRAPEZIUS,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                ],
                'difficulty' => 20,
                'movementType' => MovementType::CARDIO,
            ],
            [
                'reference' => self::MOVEMENT_AMERICAN_SWING,
                'name' => 'American Swing',
                'bodyParts' => [
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                    BodyPartData::BODY_PART_FOREARMS,
                ],
                'difficulty' => 20,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_RUSSIAN_SWING,
                'name' => 'Russian Swing',
                'bodyParts' => [
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                    BodyPartData::BODY_PART_FOREARMS,
                ],
                'difficulty' => 10,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_RUN,
                'name' => 'Run',
                'bodyParts' => [
                    BodyPartData::BODY_PART_CALVES,
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                    BodyPartData::BODY_PART_GLUTES,
                ],
                'difficulty' => 10,
                'movementType' => MovementType::CARDIO,
            ],
            [
                'reference' => self::MOVEMENT_ROW,
                'name' => 'Row',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                    BodyPartData::BODY_PART_FOREARMS,
                    BodyPartData::BODY_PART_LATISSIMUS_DORSI,
                    BodyPartData::BODY_PART_BICEPS,
                    BodyPartData::BODY_PART_RHOMBOIDS,
                    BodyPartData::BODY_PART_TRAPEZIUS,
                    BodyPartData::BODY_PART_ABDOMINALS,
                    BodyPartData::BODY_PART_LOWER_BACK,
                ],
                'difficulty' => 10,
                'movementType' => MovementType::CARDIO,
            ],
            [
                'reference' => self::MOVEMENT_BIKE_ERG,
                'name' => 'Bike Erg',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                    BodyPartData::BODY_PART_CALVES,
                ],
                'difficulty' => 10,
                'movementType' => MovementType::CARDIO,
            ],
            [
                'reference' => self::MOVEMENT_ASSAULT_BIKE,
                'name' => 'Assault Bike',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                    BodyPartData::BODY_PART_CALVES,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_RHOMBOIDS,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_PECTORALS,
                ],
                'difficulty' => 10,
                'movementType' => MovementType::CARDIO,
            ],
            [
                'reference' => self::MOVEMENT_SKI_ERG,
                'name' => 'Ski Erg',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_ABDOMINALS,
                    BodyPartData::BODY_PART_LATISSIMUS_DORSI,
                    BodyPartData::BODY_PART_BICEPS,
                    BodyPartData::BODY_PART_TRICEPS,
                ],
                'difficulty' => 10,
                'movementType' => MovementType::CARDIO,
            ],
            [
                'reference' => self::MOVEMENT_BURPEE,
                'name' => 'Burpee',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_ABDOMINALS,
                    BodyPartData::BODY_PART_LATISSIMUS_DORSI,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_PECTORALS,
                    BodyPartData::BODY_PART_CALVES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                ],
                'difficulty' => 10,
                'movementType' => MovementType::CARDIO,
            ],
            [
                'reference' => self::MOVEMENT_BURPEE_BOX_JUMP_OVER,
                'name' => 'Burpee Box Jump Over',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_ABDOMINALS,
                    BodyPartData::BODY_PART_LATISSIMUS_DORSI,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_PECTORALS,
                    BodyPartData::BODY_PART_CALVES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                ],
                'difficulty' => 20,
                'movementType' => MovementType::CARDIO,
            ],
            [
                'reference' => self::MOVEMENT_BURPEE_OVER,
                'name' => 'Burpee Over',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_ABDOMINALS,
                    BodyPartData::BODY_PART_LATISSIMUS_DORSI,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_PECTORALS,
                    BodyPartData::BODY_PART_CALVES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                ],
                'difficulty' => 15,
                'movementType' => MovementType::CARDIO,
            ],
            [
                'reference' => self::MOVEMENT_BURPEE_OVER_FACING,
                'name' => 'Burpee Over Facing',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_ABDOMINALS,
                    BodyPartData::BODY_PART_LATISSIMUS_DORSI,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_PECTORALS,
                    BodyPartData::BODY_PART_CALVES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                ],
                'difficulty' => 15,
                'movementType' => MovementType::CARDIO,
            ],
            [
                'reference' => self::MOVEMENT_BURPEE_PULL_UP,
                'name' => 'Burpee Pull Up',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_ABDOMINALS,
                    BodyPartData::BODY_PART_LATISSIMUS_DORSI,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_PECTORALS,
                    BodyPartData::BODY_PART_CALVES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                ],
                'difficulty' => 55,
                'movementType' => MovementType::CARDIO,
            ],
            [
                'reference' => self::MOVEMENT_BURPEE_MUSCLE_UP,
                'name' => 'Burpee Muscle Up',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_ABDOMINALS,
                    BodyPartData::BODY_PART_LATISSIMUS_DORSI,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_PECTORALS,
                    BodyPartData::BODY_PART_CALVES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                ],
                'difficulty' => 75,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_BURPEE_CHEST_TO_BAR_PULL_UP,
                'name' => 'Burpee Chest to Bar Pull Up',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_ABDOMINALS,
                    BodyPartData::BODY_PART_LATISSIMUS_DORSI,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_PECTORALS,
                    BodyPartData::BODY_PART_CALVES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                ],
                'difficulty' => 65,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_STRICT_CHEST_TO_BAR_PULL_UP,
                'name' => 'Strict Chest to Bar Pull Up',
                'bodyParts' => [
                    BodyPartData::BODY_PART_LATISSIMUS_DORSI,
                    BodyPartData::BODY_PART_BICEPS,
                    BodyPartData::BODY_PART_FOREARMS,
                    BodyPartData::BODY_PART_RHOMBOIDS,
                ],
                'difficulty' => 70,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_STRICT_PULL_UP,
                'name' => 'Strict Pull Up',
                'bodyParts' => [
                    BodyPartData::BODY_PART_LATISSIMUS_DORSI,
                    BodyPartData::BODY_PART_BICEPS,
                    BodyPartData::BODY_PART_FOREARMS,
                    BodyPartData::BODY_PART_RHOMBOIDS,
                ],
                'difficulty' => 60,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_MUSCLE_UP,
                'name' => 'Muscle Up',
                'bodyParts' => [
                    BodyPartData::BODY_PART_LATISSIMUS_DORSI,
                    BodyPartData::BODY_PART_BICEPS,
                    BodyPartData::BODY_PART_FOREARMS,
                    BodyPartData::BODY_PART_RHOMBOIDS,
                    BodyPartData::BODY_PART_PECTORALS,
                    BodyPartData::BODY_PART_ABDOMINALS,
                ],
                'difficulty' => 80,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_PULL_OVER,
                'name' => 'Pull Over',
                'bodyParts' => [
                    BodyPartData::BODY_PART_LATISSIMUS_DORSI,
                    BodyPartData::BODY_PART_BICEPS,
                    BodyPartData::BODY_PART_FOREARMS,
                    BodyPartData::BODY_PART_RHOMBOIDS,
                    BodyPartData::BODY_PART_PECTORALS,
                    BodyPartData::BODY_PART_ABDOMINALS,
                ],
                'difficulty' => 70,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_DIP,
                'name' => 'Dip',
                'bodyParts' => [
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_PECTORALS,
                    BodyPartData::BODY_PART_ABDOMINALS,
                    BodyPartData::BODY_PART_SHOULDERS,
                ],
                'difficulty' => 60,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_HANDSTAND_PUSH_UP,
                'name' => 'Handstand Push Up',
                'bodyParts' => [
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_PECTORALS,
                    BodyPartData::BODY_PART_ABDOMINALS,
                ],
                'difficulty' => 70,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_BURPEE_TARGET,
                'name' => 'Burpee Target',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_PECTORALS,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_ABDOMINALS,
                ],
                'difficulty' => 20,
                'movementType' => MovementType::CARDIO,
            ],
            [
                'reference' => self::MOVEMENT_BURPEE_PULL_OVER,
                'name' => 'Burpee Pull Over',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_PECTORALS,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_ABDOMINALS,
                    BodyPartData::BODY_PART_FOREARMS,
                    BodyPartData::BODY_PART_RHOMBOIDS,
                ],
                'difficulty' => 70,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_WALL_FACING_HANDSTAND_PUSH_UP,
                'name' => 'Wall Facing Handstand Push Up',
                'bodyParts' => [
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_PECTORALS,
                ],
                'difficulty' => 80,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_HANDSTAND_PIROUETTE,
                'name' => 'Handstand Pirouette',
                'bodyParts' => [
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_PECTORALS,
                ],
                'difficulty' => 80,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_HANDSTAND_WALK,
                'name' => 'Handstand Walk',
                'bodyParts' => [
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_PECTORALS,
                ],
                'difficulty' => 70,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_STRICT_HANDSTAND_PUSH_UP,
                'name' => 'Strict Handstand Push Up',
                'bodyParts' => [
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_PECTORALS,
                ],
                'difficulty' => 80,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_KIPPING_HANDSTAND_PUSH_UP,
                'name' => 'Kipping Handstand Push Up',
                'bodyParts' => [
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_PECTORALS,
                ],
                'difficulty' => 70,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_TOES_TO_BAR,
                'name' => 'Toes to Bar',
                'bodyParts' => [
                    BodyPartData::BODY_PART_ABDOMINALS,
                    BodyPartData::BODY_PART_LATISSIMUS_DORSI,
                    BodyPartData::BODY_PART_BICEPS,
                    BodyPartData::BODY_PART_FOREARMS,
                    BodyPartData::BODY_PART_HIP_FLEXORS,
                ],
                'difficulty' => 40,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_TOES_TO_RING,
                'name' => 'Toes to Ring',
                'bodyParts' => [
                    BodyPartData::BODY_PART_ABDOMINALS,
                    BodyPartData::BODY_PART_LATISSIMUS_DORSI,
                    BodyPartData::BODY_PART_BICEPS,
                    BodyPartData::BODY_PART_FOREARMS,
                    BodyPartData::BODY_PART_HIP_FLEXORS,
                ],
                'difficulty' => 40,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_STRICT_TOES_TO_BAR,
                'name' => 'Strict Toes to Bar',
                'bodyParts' => [
                    BodyPartData::BODY_PART_ABDOMINALS,
                    BodyPartData::BODY_PART_LATISSIMUS_DORSI,
                    BodyPartData::BODY_PART_BICEPS,
                    BodyPartData::BODY_PART_FOREARMS,
                    BodyPartData::BODY_PART_HIP_FLEXORS,
                ],
                'difficulty' => 50,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_KNEES_TO_ELBOWS,
                'name' => 'Knees to Elbows',
                'bodyParts' => [
                    BodyPartData::BODY_PART_ABDOMINALS,
                    BodyPartData::BODY_PART_LATISSIMUS_DORSI,
                    BodyPartData::BODY_PART_BICEPS,
                    BodyPartData::BODY_PART_FOREARMS,
                    BodyPartData::BODY_PART_HIP_FLEXORS,
                ],
                'difficulty' => 50,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_ALTERNATE_PISTOL_SQUAT,
                'name' => 'Alternate Pistol Squat',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                ],
                'difficulty' => 60,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_PISTOL_SQUAT,
                'name' => 'Pistol Squat',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                ],
                'difficulty' => 60,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_TURKISH_GET_UP,
                'name' => 'Turkish Get Up',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_PECTORALS,
                ],
                'difficulty' => 60,
                'movementType' => MovementType::WEIGHTLIFTING,
            ],
            [
                'reference' => self::MOVEMENT_GHD_SIT_UP,
                'name' => 'GHD Sit Up',
                'bodyParts' => [
                    BodyPartData::BODY_PART_ABDOMINALS,
                    BodyPartData::BODY_PART_HIP_FLEXORS,
                    BodyPartData::BODY_PART_QUADRICEPS,
                ],
                'difficulty' => 50,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_GHD_BACK_EXTENSION,
                'name' => 'GHD Back Extension',
                'bodyParts' => [
                    BodyPartData::BODY_PART_HAMSTRINGS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_LOWER_BACK,
                ],
                'difficulty' => 50,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_GHD_HIP_EXTENSION,
                'name' => 'GHD Hip Extension',
                'bodyParts' => [
                    BodyPartData::BODY_PART_HAMSTRINGS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_LOWER_BACK,
                ],
                'difficulty' => 50,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference'=> self::MOVEMENT_CARRY,
                'name' => 'Carry',
                'bodyParts' => [
                    BodyPartData::BODY_PART_FOREARMS,
                    BodyPartData::BODY_PART_BICEPS,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_TRAPEZIUS,
                    BodyPartData::BODY_PART_CALVES,
                    BodyPartData::BODY_PART_QUADRICEPS,
                ],
                'difficulty' => 20,
                'movementType' => MovementType::STRONGMAN,
            ],
            [
                'reference'=> self::MOVEMENT_SLED_DRAG,
                'name' => 'Sled Drag',
                'bodyParts' => [
                    BodyPartData::BODY_PART_FOREARMS,
                    BodyPartData::BODY_PART_BICEPS,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_TRAPEZIUS,
                    BodyPartData::BODY_PART_CALVES,
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                ],
                'difficulty' => 20,
                'movementType' => MovementType::STRONGMAN,
            ],
            [
                'reference'=> self::MOVEMENT_SLED_PUSH,
                'name' => 'Sled Push',
                'bodyParts' => [
                    BodyPartData::BODY_PART_FOREARMS,
                    BodyPartData::BODY_PART_BICEPS,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_TRAPEZIUS,
                    BodyPartData::BODY_PART_CALVES,
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                    BodyPartData::BODY_PART_GLUTES,
                ],
                'difficulty' => 20,
                'movementType' => MovementType::STRONGMAN,
            ],
            [
                'reference'=> self::MOVEMENT_SLED_PULL,
                'name' => 'Sled Pull',
                'bodyParts' => [
                    BodyPartData::BODY_PART_FOREARMS,
                    BodyPartData::BODY_PART_BICEPS,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_TRAPEZIUS,
                    BodyPartData::BODY_PART_CALVES,
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                    BodyPartData::BODY_PART_GLUTES,
                ],
                'difficulty' => 20,
                'movementType' => MovementType::STRONGMAN,
            ],
            [
                'reference'=> self::MOVEMENT_SIT_UP,
                'name' => 'Sit Up',
                'bodyParts' => [
                    BodyPartData::BODY_PART_ABDOMINALS,
                    BodyPartData::BODY_PART_HIP_FLEXORS,
                ],
                'difficulty' => 20,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference'=> self::MOVEMENT_SHUTTLE_RUN,
                'name' => 'Shuttle Run',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_CALVES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_LOWER_BACK,
                ],
                'difficulty' => 20,
                'movementType' => MovementType::CARDIO,
            ],
            [
                'reference'=> self::MOVEMENT_BIKE,
                'name' => 'Bike',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_CALVES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_LOWER_BACK,
                ],
                'difficulty' => 20,
                'movementType' => MovementType::CARDIO,
            ],
            [
                'reference' => self::MOVEMENT_RUN_AND_BIKE,
                'name' => 'Run and Bike',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_CALVES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_LOWER_BACK,
                ],
                'difficulty' => 20,
                'movementType' => MovementType::CARDIO,
            ],
            [
                'reference' => self::MOVEMENT_SWIM,
                'name' => 'Swim',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                    BodyPartData::BODY_PART_LOWER_BACK,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_PECTORALS,
                    BodyPartData::BODY_PART_LATISSIMUS_DORSI,
                ],
                'difficulty' => 20,
                'movementType' => MovementType::CARDIO,
            ],
            [
                'reference' => self::MOVEMENT_PADDLE,
                'name' => 'Paddle',
                'bodyParts' => [
                    BodyPartData::BODY_PART_LOWER_BACK,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_PECTORALS,
                    BodyPartData::BODY_PART_TRAPEZIUS,
                    BodyPartData::BODY_PART_LATISSIMUS_DORSI,
                ],
                'difficulty' => 20,
                'movementType' => MovementType::CARDIO,
            ],
            [
                'reference' => self::MOVEMENT_HIGH_BOX_JUMP,
                'name' => 'High Box Jump',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_CALVES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                ],
                'difficulty' => 30,
                'movementType' => MovementType::PLYOMETRIC,
            ],
            [
                'reference' => self::MOVEMENT_HIGH_BOX_JUMP_OVER,
                'name' => 'High Box Jump Over',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_CALVES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                ],
                'difficulty' => 30,
                'movementType' => MovementType::PLYOMETRIC,
            ],
            [
                'reference' => self::MOVEMENT_BOX_STEP_UP,
                'name' => 'Box Step Up',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_GLUTES,
                    BodyPartData::BODY_PART_HAMSTRINGS,
                ],
                'difficulty' => 10,
                'movementType' => MovementType::PLYOMETRIC,
            ],
            [
                'reference' => self::MOVEMENT_WALL_WALK,
                'name' => 'Wall Walk',
                'bodyParts' => [
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_PECTORALS,
                    BodyPartData::BODY_PART_ABDOMINALS,
                    BodyPartData::BODY_PART_TRAPEZIUS,
                ],
                'difficulty' => 70,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_DEFICIT_HANDSTAND_PUSH_UP,
                'name' => 'Deficit Handstand Push Up',
                'bodyParts' => [
                    BodyPartData::BODY_PART_SHOULDERS,
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_PECTORALS,
                ],
                'difficulty' => 80,
                'movementType' => MovementType::GYMNASTIC,
            ],
            [
                'reference' => self::MOVEMENT_BROAD_JUMP,
                'name' => 'Broad Jump',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                ],
                'difficulty' => 20,
                'movementType' => MovementType::PLYOMETRIC,
            ],
            [
                'reference' => self::MOVEMENT_BURPEE_BROAD_JUMP,
                'name' => 'Burpee Broad Jump',
                'bodyParts' => [
                    BodyPartData::BODY_PART_QUADRICEPS,
                    BodyPartData::BODY_PART_GLUTES,
                ],
                'difficulty' => 30,
                'movementType' => MovementType::PLYOMETRIC,
            ],
            [
                'reference' => self::MOVEMENT_PUSH_UP,
                'name' => 'Push Up',
                'bodyParts' => [
                    BodyPartData::BODY_PART_TRICEPS,
                    BodyPartData::BODY_PART_PECTORALS,
                    BodyPartData::BODY_PART_ABDOMINALS,
                    BodyPartData::BODY_PART_SHOULDERS,
                ],
                'difficulty' => 30,
                'movementType' => MovementType::GYMNASTIC,
            ],
        ];
    }
}
