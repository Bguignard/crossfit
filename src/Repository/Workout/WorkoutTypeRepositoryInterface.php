<?php

namespace App\Repository\Workout;

use App\Entity\Workout\WorkoutType;

interface WorkoutTypeRepositoryInterface
{
    public function persist(WorkoutType $workoutType): WorkoutType;
}
