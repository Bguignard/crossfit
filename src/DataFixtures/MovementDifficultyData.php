<?php

namespace App\DataFixtures;

use App\Entity\Workout\Enum\MovementDifficultyEnum;
use App\Entity\Workout\MovementDifficulty;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MovementDifficultyData extends Fixture
{
    public const string MOVEMENT_DIFFICULTY_BEGINNER = 'movement_difficulty_beginner';
    public const string MOVEMENT_DIFFICULTY_INTERMEDIATE = 'movement_difficulty_intermediate';
    public const string MOVEMENT_DIFFICULTY_RX = 'movement_difficulty_rx';
    public const string MOVEMENT_DIFFICULTY_ELITE = 'movement_difficulty_elite';

    public function load(ObjectManager $manager): void
    {
        foreach ($this->getMovementDifficulties() as $reference => $movementDifficulty) {
            $movementDifficultyEntity = new MovementDifficulty($movementDifficulty['name']);
            $manager->persist($movementDifficultyEntity);
            $this->addReference($reference, $movementDifficultyEntity);
        }
        $manager->flush();
    }

    private function getMovementDifficulties(): array
    {
        return [
            self::MOVEMENT_DIFFICULTY_BEGINNER => [
                'name' => MovementDifficultyEnum::BEGINNER,
            ],
            self::MOVEMENT_DIFFICULTY_INTERMEDIATE => [
                'name' => MovementDifficultyEnum::INTERMEDIATE,
            ],
            self::MOVEMENT_DIFFICULTY_RX => [
                'name' => MovementDifficultyEnum::RX,
            ],
            self::MOVEMENT_DIFFICULTY_ELITE => [
                'name' => MovementDifficultyEnum::ELITE,
            ],
        ];
    }
}
