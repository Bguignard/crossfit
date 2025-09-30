<?php

namespace App\Services\Workout;

use App\Entity\Workout\Workout;
use App\Entity\WorkoutGeneration\WorkoutGeneration;

interface WorkoutGeneratorServiceInterface
{
    public function generateWorkout(WorkoutGeneration $workoutGeneration): Workout;
}
