<?php

namespace App\Services\Workout;

use App\Entity\Workout\WorkoutOrigin;

interface WorkoutOriginServiceInterface
{
    public function insertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin;
}
