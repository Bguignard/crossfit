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

    public function getExistingOrInsertNewWorkoutOrigin(string $name, ?int $year): WorkoutOrigin
    {
        $workoutOriginName = $this->workoutOriginNameRepository->findOneBy(['name' => $name]);
        if ($workoutOriginName === null) {
            $workoutOriginName = $this->workoutOriginNameRepository->findOneBy(['name' => WorkoutOriginNameEnum::CUSTOM->value]);
        }

        $workoutOrigin = $this->workoutOriginRepository->findOneBy(['name' => $workoutOriginName->getId(), 'year' => $year]);
        if ($workoutOrigin !== null) {
            return $workoutOrigin;
        }

        $workoutOrigin = new WorkoutOrigin(
            $workoutOriginName,
            $year
        );

        return $this->workoutOriginRepository->persist($workoutOrigin);
    }
}
