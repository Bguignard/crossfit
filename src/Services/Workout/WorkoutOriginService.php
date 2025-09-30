<?php

namespace App\Services\Workout;

use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use App\Entity\Workout\WorkoutOrigin;
use App\Repository\Workout\WorkoutOriginNameRepositoryInterface;
use App\Repository\Workout\WorkoutOriginRepositoryInterface;

final readonly class WorkoutOriginService implements WorkoutOriginServiceInterface
{
    public function __construct(
        private WorkoutOriginRepositoryInterface $workoutOriginRepository,
        private WorkoutOriginNameRepositoryInterface $workoutOriginNameRepository,
    ) {
    }

    public function insertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
    {
        $workoutOriginName = $this->workoutOriginNameRepository->findOneBy(['name' => $name]);
        $workoutOrigin = $this->workoutOriginRepository->findOneBy(['name' => $workoutOriginName->getId(), 'year' => $year]);
        if ($workoutOrigin !== null) {
            return $workoutOrigin;
        }

        $customWorkoutOriginName = $this->workoutOriginNameRepository->findOneBy(['name' => WorkoutOriginNameEnum::CUSTOM->value]);
        $workoutOrigin = new WorkoutOrigin(
            $customWorkoutOriginName,
            $year
        );

        return $this->workoutOriginRepository->persist($workoutOrigin);
    }
}
