<?php

namespace App\Services\Workout;

use App\Entity\Workout\Enum\MeasureUnitEnum;
use App\Entity\Workout\Movement;
use App\Entity\Workout\MovementCluster;

interface MovementClusterGeneratorServiceInterface
{
    public function generateMovementCluster(
        Movement $movement,
        MeasureUnitEnum $movementMeasureUnit,
        int $allowedTimeInSeconds,
        array $implements,
        ?MeasureUnitEnum $chosenImplementMeasureUnit,
        ?float $implementIntensityValue
    ): MovementCluster;
}
