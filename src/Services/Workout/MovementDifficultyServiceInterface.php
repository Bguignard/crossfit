<?php

namespace App\Services\Workout;

use App\Entity\Workout\MovementDifficulty;

interface MovementDifficultyServiceInterface
{
    /**
     * @return array<MovementDifficulty>
     */
    public function getWorkoutDifficultiesFromOne(MovementDifficulty $workoutDifficultyEntity): array;
}
