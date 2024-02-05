<?php

namespace App\Services\Workout;

use App\Entity\Workout\Enum\MovementTypeEnum;
use App\Entity\Workout\Movement;
use App\Repository\Workout\MovementRepositoryInterface;

interface MovementGeneratorServiceInterface
{
    public function generateMovement(?array $availableImplements, ?int $maxDifficulty, ?array $forbiddenMovements, MovementTypeEnum $movementType): Movement;

}
