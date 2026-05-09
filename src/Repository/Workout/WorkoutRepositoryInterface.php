<?php

namespace App\Repository\Workout;

use App\Entity\Workout\Workout;

interface WorkoutRepositoryInterface
{
    public function persist(Workout $workout): Workout;

    public function getByName(string $name): ?Workout;

    public function getWorkoutsNames(): array;

    public function getWorkoutsNamesByOrigin(string $originId): array;

    public function getWorkoutsByNameLike(string $name): array;
}
