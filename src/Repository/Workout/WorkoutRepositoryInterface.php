<?php

namespace App\Repository\Workout;

use App\Entity\Workout\Workout;

interface WorkoutRepositoryInterface
{
    public function getByName(string $name): ?Workout;

    public function getWorkoutsNames(): array;

    public function getWorkoutsNamesByOrigin(string $originId): array;
}
