<?php

namespace App\Repository\Workout;

use App\Entity\Workout\SimpleWorkout;

interface SimpleWorkoutRepositoryInterface
{
    public function persist(SimpleWorkout $workoutOriginName): SimpleWorkout;
}
