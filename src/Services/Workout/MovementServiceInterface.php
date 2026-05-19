<?php

namespace App\Services\Workout;

use App\Entity\Workout\Movement;
use App\Entity\WorkoutGeneration\WorkoutGeneration;
use Doctrine\Common\Collections\Collection;

interface MovementServiceInterface
{
    public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array;

    /**
     * @return Movement[]
     */
    public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array;

    /**
     * @param Movement[] $movements
     *
     * @return Movement[]
     */
    public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array;

    /**
     * @return Movement[]
     */
    public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array;

    /**
     * @return Movement[]
     */
    public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array;

    public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array;
}
