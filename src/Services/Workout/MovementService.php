<?php

namespace App\Services\Workout;

use App\Entity\Workout\Movement;
use App\Entity\WorkoutGeneration\WorkoutGeneration;
use App\Repository\Workout\MovementRepositoryInterface;
use Doctrine\Common\Collections\Collection;

readonly class MovementService implements MovementServiceInterface
{
    public function __construct(
        public MovementRepositoryInterface $movementRepository,
        public MovementDifficultyService $movementDifficultyService,
        public MuscleServiceInterface $muscleService,
    ) {
    }

    public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
    {
        // todo : we should remove similar movements for example if we have 2 different types of push ups, jerk, clean, we should not have both in the workout
        $possibleMovements = $this->movementRepository->getMovementsByMovementTypesAndDifficultyAndImplements(
            $workoutGeneration->getMovementTypes()->toArray(),
            $this->movementDifficultyService->getWorkoutDifficultiesFromOne($workoutGeneration->getMovementDifficulty()),
            array_merge($workoutGeneration->getBannedMovements()->toArray(), $workoutGeneration->getMandatoryMovements()->toArray()),
            $workoutGeneration->getAvailableImplements()->toArray(),
        );
        shuffle($possibleMovements);

        return $this->getSimpleWorkoutMovementsFromPossibleMovements($possibleMovements, $workoutGeneration);
    }

    /**
     * @param Movement[] $movements
     *
     * @return Movement[]
     */
    public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
    {
        foreach ($movements as $movement) {
            foreach ($movement->getPossibleImplements() as $key => $implement) {
                if (!$possibleImplements->contains($implement)) {
                    $movement->getPossibleImplements()->remove($key);
                }
            }
        }

        return $movements;
    }

    /**
     * @return Movement[]
     */
    public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
    {
        $muscles = $this->muscleService->getMusclesFromBodyParts($workoutGeneration->getMandatoryBodyParts()->toArray());

        $possibleMovements = $this->movementRepository->getMovementsByMovementTypesAndDifficultyAndImplementsAndMuscles(
            $workoutGeneration->getMovementTypes()->toArray(),
            $this->movementDifficultyService->getWorkoutDifficultiesFromOne($workoutGeneration->getMovementDifficulty()),
            $workoutGeneration->getBannedMovements()->toArray(),
            $workoutGeneration->getAvailableImplements()->toArray(),
            $muscles
        );

        shuffle($possibleMovements);

        return $this->getSimpleWorkoutMovementsFromPossibleMovements($possibleMovements, $workoutGeneration);
    }

    public function getSimpleWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
    {
        // get already used type of movements
        $usedMovementTypes = [];
        foreach ($workoutGeneration->getMandatoryMovements() as $mandatoryMovement) {
            $usedMovementTypes[$mandatoryMovement->getMovementType()->getId()->toString()] = $mandatoryMovement->getMovementType();
        }

        // get not used type of movements
        $notUsedMovementTypes = [];
        foreach ($workoutGeneration->getMovementTypes() as $movementType) {
            if (!array_key_exists($movementType->getId()->toString(), $usedMovementTypes)) {
                $notUsedMovementTypes[$movementType->getId()->toString()] = $movementType;
            }
        }

        $movementsInWorkout = $workoutGeneration->getMandatoryMovements()->toArray();

        // first, we fill with not used type of movements
        foreach ($notUsedMovementTypes as $movementType) {
            foreach ($possibleMovements as $key => $movement) {
                if ($movement->getMovementTypes()->contains($movementType) && count($movementsInWorkout) < $workoutGeneration->getNumberOfDifferentMovements()) {
                    $movementsInWorkout[] = $movement;
                    unset($possibleMovements[$key]);
                    break;
                }
            }
            if (count($movementsInWorkout) >= $workoutGeneration->getNumberOfDifferentMovements()) {
                break;
            }
        }

        // once we filled with not used type of movements, we fill with any possible movement
        while (count($movementsInWorkout) < $workoutGeneration->getNumberOfDifferentMovements()) {
            $movementsInWorkout[] = array_pop($possibleMovements);
        }

        return $movementsInWorkout;
    }
}
