<?php

namespace App\Services\Workout;

use App\Entity\Workout\Enum\WorkoutTypeEnum;
use App\Entity\Workout\Workout;

interface WorkoutGeneratorServiceInterface
{
    public function generateWorkout(
        ?string $name,
        array $workoutMovementTypes,
        ?WorkoutTypeEnum $workoutType,
        int $numberOfDifferentMovements,
        int $workoutTimeCap,
        int $cardioIntensity,
        int $gymnasticIntensity,
        int $weightliftingIntensity,
        int $weightIntensity,
        bool $intervals,
        ?int $intervalsTime,
        ?int $intervalsRestTime,
        ?array $mandatoryMovements,
        ?array $mandatoryImplements,
        ?int $maxDifficulty
    ): Workout;
}
