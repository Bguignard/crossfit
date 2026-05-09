<?php

namespace App\Services\Workout;

use App\Entity\Workout\Workout;
use App\Entity\WorkoutGeneration\WorkoutGeneration;

interface WorkoutCreatorServiceInterface
{
    public function createWorkout(WorkoutGeneration $workoutGeneration): Workout;
}
