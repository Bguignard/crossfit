<?php

namespace App\DataFixtures;

use App\Entity\Workout\Enum\MovementTypeEnum;
use App\Entity\Workout\MovementType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MovementTypeData extends Fixture
{
    public const string MOVEMENT_TYPE_GYMNASTIC = 'movement_type_gymnastic';
    public const string MOVEMENT_TYPE_WEIGHTLIFTING = 'movement_type_weightlifting';
    public const string MOVEMENT_TYPE_CARDIO = 'movement_type_cardio';
    public const string MOVEMENT_TYPE_STRONGMAN = 'movement_type_strongman';
    public const string MOVEMENT_TYPE_BODYBUILDING = 'movement_type_bodybuilding';
    public const string MOVEMENT_TYPE_PLYOMETRIC = 'movement_type_plyometric';
    public const string MOVEMENT_TYPE_WARM_UP = 'movement_type_warm_up';
    public const string MOVEMENT_TYPE_STRETCHING = 'movement_type_stretching';

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getMovementTypes() as $reference => $movementType) {
            $workoutTypeEntity = new MovementType($movementType['name']);
            $manager->persist($workoutTypeEntity);
            $this->addReference($reference, $workoutTypeEntity);
        }
        $manager->flush();
    }

    private function getMovementTypes(): array
    {
        return [
            self::MOVEMENT_TYPE_GYMNASTIC => [
                'name' => MovementTypeEnum::GYMNASTIC,
            ],
            self::MOVEMENT_TYPE_WEIGHTLIFTING => [
                'name' => MovementTypeEnum::WEIGHTLIFTING,
            ],
            self::MOVEMENT_TYPE_CARDIO => [
                'name' => MovementTypeEnum::CARDIO,
            ],
            self::MOVEMENT_TYPE_STRONGMAN => [
                'name' => MovementTypeEnum::STRONGMAN,
            ],
            self::MOVEMENT_TYPE_BODYBUILDING => [
                'name' => MovementTypeEnum::BODYBUILDING,
            ],
            self::MOVEMENT_TYPE_PLYOMETRIC => [
                'name' => MovementTypeEnum::PLYOMETRIC,
            ],
            self::MOVEMENT_TYPE_WARM_UP => [
                'name' => MovementTypeEnum::WARM_UP,
            ],
            self::MOVEMENT_TYPE_STRETCHING => [
                'name' => MovementTypeEnum::STRETCHING,
            ],
        ];
    }
}
