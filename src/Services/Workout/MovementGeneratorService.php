<?php

namespace App\Services\Workout;

use App\Entity\Workout\Enum\MovementTypeEnum;
use App\Entity\Workout\Movement;
use App\Repository\Workout\MovementRepositoryInterface;

readonly class MovementGeneratorService
{
    public function __construct(
        private MovementRepositoryInterface $movementRepository
    ) {
    }

    public function generateMovement(?array $availableImplements, ?int $maxDifficulty, ?array $forbiddenMovements, MovementTypeEnum $movementType): Movement
    {
        return $this->movementRepository->getMovementByDifficultyAndImplementsAndForbiddenMovementsAndType($availableImplements, $maxDifficulty, $forbiddenMovements, $movementType);
    }
}
