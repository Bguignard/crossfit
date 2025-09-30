<?php

namespace App\Repository\Workout;

use App\Entity\Workout\MovementDifficulty;

interface MovementDifficultyRepositoryInterface
{
    public function persist(MovementDifficulty $movementDifficulty): MovementDifficulty;
}
