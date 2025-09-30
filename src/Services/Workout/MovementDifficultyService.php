<?php

namespace App\Services\Workout;

use App\Entity\Workout\Enum\MovementDifficultyEnum;
use App\Entity\Workout\MovementDifficulty;
use App\Repository\Workout\MovementDifficultyRepositoryInterface;

readonly class MovementDifficultyService
{
    public function __construct(
        private MovementDifficultyRepositoryInterface $movementDifficultyRepository,
    ) {
    }

    /**
     * @return array<MovementDifficulty>
     */
    public function getWorkoutDifficultiesFromOne(MovementDifficulty $workoutDifficultyEntity): array
    {
        $difficultiesAsEnumToGet = [];
        $difficultiesEntitiesToGet = [];

        if ($workoutDifficultyEntity->getNameAsEnum() === MovementDifficultyEnum::ELITE) {
            $difficultiesAsEnumToGet[] = MovementDifficultyEnum::BEGINNER->value;
            $difficultiesAsEnumToGet[] = MovementDifficultyEnum::INTERMEDIATE->value;
            $difficultiesAsEnumToGet[] = MovementDifficultyEnum::RX->value;
            $difficultiesAsEnumToGet[] = MovementDifficultyEnum::ELITE->value;
        } elseif ($workoutDifficultyEntity->getNameAsEnum() === MovementDifficultyEnum::RX) {
            $difficultiesAsEnumToGet[] = MovementDifficultyEnum::BEGINNER->value;
            $difficultiesAsEnumToGet[] = MovementDifficultyEnum::INTERMEDIATE->value;
            $difficultiesAsEnumToGet[] = MovementDifficultyEnum::RX->value;
        } elseif ($workoutDifficultyEntity->getNameAsEnum() === MovementDifficultyEnum::INTERMEDIATE) {
            $difficultiesAsEnumToGet[] = MovementDifficultyEnum::BEGINNER->value;
            $difficultiesAsEnumToGet[] = MovementDifficultyEnum::INTERMEDIATE->value;
        } else {
            $difficultiesAsEnumToGet[] = MovementDifficultyEnum::BEGINNER->value;
        }

        foreach ($difficultiesAsEnumToGet as $difficulty) {
            $difficultyEntity = $this->movementDifficultyRepository->findOneBy(['name' => $difficulty]);
            if ($difficultyEntity === null) {
                throw new \RuntimeException('Movement difficulty entity not found for difficulty: '.$difficulty);
            }
            $difficultiesEntitiesToGet[] = $difficultyEntity;
        }

        return $difficultiesEntitiesToGet;
    }
}
