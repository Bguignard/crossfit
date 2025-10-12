<?php

namespace App\Services\Workout;

use App\Entity\Workout\SimpleWorkout;
use App\Entity\WorkoutGeneration\WorkoutGeneration;

interface SimpleWorkoutCreatorServiceInterface
{
    public function createSimpleWorkout(WorkoutGeneration $workoutGeneration): SimpleWorkout;
}
