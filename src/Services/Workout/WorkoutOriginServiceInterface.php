<?php

namespace App\Services\Workout;

use App\Entity\Workout\WorkoutOrigin;

interface WorkoutOriginServiceInterface
{
    public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin;
}
