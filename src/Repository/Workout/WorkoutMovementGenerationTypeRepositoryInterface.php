<?php

namespace App\Repository\Workout;

use App\Entity\Workout\WorkoutMovementGenerationType;

interface WorkoutMovementGenerationTypeRepositoryInterface
{
    public function persist(WorkoutMovementGenerationType $workoutMovementGenerationType): WorkoutMovementGenerationType;
}
