<?php

namespace App\Services\Workout;

use App\Entity\Workout\Workout;
use App\Entity\Workout\WorkoutType;

interface WorkoutGeneratorServiceInterface
{
    public function generateWorkout(
        string $name,
        array $workoutMovementTypes,
        WorkoutType $workoutTypeEntity,
        int $numberOfDifferentMovements,
        int $workoutTimeCap,
        array $mandatoryMovements,
        array $bannedMovements,
        array $availableImplements,
        int $cardioIntensity,
        int $gymnasticIntensity,
        int $weightliftingIntensity,
        int $weightIntensity,
        int $difficultyOfMovements,
        ?int $intervalsTime,
        ?int $intervalsRestTime,
        ?int $maxNumberOfRounds = 10,
    ): Workout;
}
