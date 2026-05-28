<?php

namespace App\Services\Workout;

use App\Entity\Workout\Workout;
use App\Entity\WorkoutGeneration\WorkoutGeneration;

interface WorkoutCreatorServiceInterface
{
    public function createWorkout(WorkoutGeneration $workoutGeneration): Workout;

    /**
     * @return list<array{title: string, intent: string, format: string, movementNames: list<string>, summary: string}>
     */
    public function createWorkoutVariants(WorkoutGeneration $workoutGeneration): array;
}
