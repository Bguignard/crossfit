<?php

namespace App\Services\Workout;

use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use App\Entity\Workout\WorkoutOrigin;
use App\Repository\Workout\WorkoutOriginRepositoryInterface;

final readonly class WorkoutOriginService implements WorkoutOriginServiceInterface
{
    public function __construct(
        private WorkoutOriginRepositoryInterface $workoutOriginRepository,
    ) {
    }

    public function insertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
    {
        $workoutOrigin = $this->workoutOriginRepository->findOneBy(['name' => $name, 'year' => $year]);
        if ($workoutOrigin !== null) {
            return $workoutOrigin;
        }

        $workoutOrigin = new WorkoutOrigin(
            $this->workoutOriginRepository->findOneBy(['name' => WorkoutOriginNameEnum::CUSTOM->value, 'year' => $year]),
            $year
        );

        return $this->workoutOriginRepository->persist($workoutOrigin);
    }
}
