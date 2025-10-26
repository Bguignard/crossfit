<?php

namespace App\DataProvider;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\WorkoutGenerationPossibleMovementsDto;
use App\Repository\Workout\MovementRepository;
use App\Repository\WorkoutGeneration\WorkoutGenerationRepository;
use App\Services\Workout\MovementDifficultyService;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @implements ProviderInterface<WorkoutGenerationPossibleMovementsDto>
 */
class WorkoutGenerationPossibleMovementsProvider implements ProviderInterface
{
    public function __construct(
        public MovementRepository $movementRepository,
        public WorkoutGenerationRepository $workoutGenerationRepository,
        public MovementDifficultyService $movementDifficultyService,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $workoutGenerationId = $uriVariables['workoutGenerationId'] ?? null;
        if ($workoutGenerationId === null) {
            return null;
        }

        $workoutGeneration = $this->workoutGenerationRepository->find($workoutGenerationId);
        if ($workoutGeneration === null) {
            return null;
        }

        if ($operation instanceof CollectionOperationInterface) {
            return [];
        }
        $movementsArray = $this->movementRepository->getMovementsByMovementTypesAndDifficultyAndImplementsAndMuscles(
            $workoutGeneration->getMovementTypes()->toArray(),
            $this->movementDifficultyService->getWorkoutDifficultiesFromOne($workoutGeneration->getMovementDifficulty()),
            $workoutGeneration->getBannedMovements()->toArray(),
            $workoutGeneration->getAvailableImplements()->toArray(),
            $workoutGeneration->getMandatoryBodyParts()->toArray(),
        );
        $movements = new ArrayCollection();

        foreach ($movementsArray as $movement) {
            $movements->add($movement);
        }

        return new WorkoutGenerationPossibleMovementsDto(
            workoutGenerationId: $workoutGeneration->getId(),
            possibleMovements: $movements
        );
    }
}
