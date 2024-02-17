<?php

namespace App\Services\Workout;

use App\Entity\Workout\Enum\MeasureUnitEnum;
use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\MovementCluster;
use App\Entity\Workout\MovementExecutionTimeForMeasureUnit;

readonly class MovementClusterGeneratorService implements MovementClusterGeneratorServiceInterface
{
    public function generateMovementCluster(
        Movement $movement,
        MeasureUnitEnum $movementMeasureUnit,
        int $allowedTimeInSeconds,
        array $implements,
        ?MeasureUnitEnum $chosenImplementMeasureUnit,
        ?float $implementIntensityValue
    ): MovementCluster {
        // Implement
        $possibleImplementsIds = array_map(
            fn (Implement $implement) => $implement->getId()->toBinary(),
            $movement->getPossibleImplements()->toArray()
        );

        // Movement measure unit
        $possibleMovementRepUnits = array_map(
            fn (MovementExecutionTimeForMeasureUnit $movementExecutionTimeForMeasureUnit) => $movementExecutionTimeForMeasureUnit->getMeasureUnit()->value,
            $movement->getMovementExecutionTimeForMeasureUnits()->toArray()
        );
        if (!in_array($movementMeasureUnit->value, $possibleMovementRepUnits)) {
            throw new \InvalidArgumentException(sprintf('Movement measure unit %s is not allowed for movement %s', $movementMeasureUnit->name, $movement->getName()));
        }

        // Implement measure unit
        $isImplementMeasureUnitMeasureUnitCorrect = null === $chosenImplementMeasureUnit;
        foreach ($implements as $implement) {
            if (!in_array($implement->getId()->toBinary(), $possibleImplementsIds)) {
                throw new \InvalidArgumentException(sprintf('Implement with id %s is not allowed for movement with id %s', $implement->getId()->toBinary(), $movement->getId()->toBinary()));
            }
            foreach ($implement->getImplementTypeOfAdjustableMeasure()->getMeasureUnits() as $measureUnit) {
                if ($measureUnit->getNameAsEnum() === $chosenImplementMeasureUnit) {
                    $isImplementMeasureUnitMeasureUnitCorrect = true;
                }
            }
        }
        if (false === $isImplementMeasureUnitMeasureUnitCorrect) {
            throw new \InvalidArgumentException(sprintf('Main implement measure unit %s is not allowed for implement %s', $chosenImplementMeasureUnit->name, $movement->getName()));
        }

        // Reps
        $numberOfRepetitions = round($allowedTimeInSeconds / ($movement->getMovementExecutionTimeForMeasureUnits()->first()->getExecutionTimeInMilliseconds() / 1000));

        return new MovementCluster(
            $numberOfRepetitions,
            $movementMeasureUnit,
            $implements,
            $movement,
            $implementIntensityValue, // depends on rx standart + intensity needed
            $chosenImplementMeasureUnit,
        );
    }
}
