<?php

namespace App\Services\Workout;

use App\Entity\Workout\Enum\MovementTypeEnum;
use App\Entity\Workout\MovementCluster;

readonly class MovementClusterGeneratorService
{
    public function __construct(
        private MovementGeneratorService $movementGeneratorService,
    ) {
    }

    public function generateMovementCluster(?array $availableImplements, ?int $maxDifficulty, ?array $forbiddenMovements, MovementTypeEnum $movementType): MovementCluster
    {
        $movement = $this->movementGeneratorService->generateMovement($availableImplements, $maxDifficulty, $forbiddenMovements, $movementType);
        $implement = $movement->getPossibleImplements()->first() ?? null;

        return new MovementCluster(
            0, // todo : calculate it later with the time we have
            $movement->getMovementExecutionTimeForMeasureUnits()->first()->getMeasureUnitEnum(),
            $implement,
            $movement,
            $implement ?? 0.0, // depends on rx standart + intensity needed
            $implement?->getImplementTypeOfAdjustableMeasureUnit()?->getMeasureUnitEnum(),
        );
    }
}
