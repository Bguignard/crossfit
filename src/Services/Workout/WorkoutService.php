<?php

namespace App\Services\Workout;

use App\Repository\Workout\WorkoutRepositoryInterface;

readonly class WorkoutService
{
    public function __construct(
        public WorkoutRepositoryInterface $workoutRepository
    ) {
    }
}
