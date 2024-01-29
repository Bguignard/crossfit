<?php

namespace App\Repository\Workout;

use App\Entity\Workout\Enum\MovementTypeEnum;
use App\Entity\Workout\Movement;

interface MovementRepositoryInterface
{
    public function getRandomMovement(?int $maximumDifficulty = 100, ?array $forbiddenMovements = []): Movement;

    public function getMovementByDifficultyAndImplementsAndForbiddenMovementsAndType(?array $availableImplements, ?int $maxDifficulty, ?array $forbiddenMovements, MovementTypeEnum $movementType): ?Movement;
}
