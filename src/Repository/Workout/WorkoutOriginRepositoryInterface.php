<?php

namespace App\Repository\Workout;

use App\Entity\Workout\WorkoutOrigin;

interface WorkoutOriginRepositoryInterface
{
    public function persist(WorkoutOrigin $workoutType): WorkoutOrigin;
}
