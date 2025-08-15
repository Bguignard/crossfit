<?php

namespace App\Dto\Converters;

use App\Dto\Workout\MovementDTO;
use App\Entity\ConvertibleToDTOInterface;
use App\Entity\Workout\Movement;

class MovementEntityToDTOConverter implements EntityToDTOConverterInterface
{
    public static function createFromEntity(ConvertibleToDTOInterface $movement): MovementDTO
    {
        if (!($movement instanceof Movement)) {
            throw new \InvalidArgumentException(sprintf('Entity must be of type % ', Movement::class));
        }

        return new MovementDTO(
            $movement->getId(),
            $movement->getName(),
            $movement->getDifficulty(),
            $movement->getMuscles()->toArray(),
            $movement->getMovementType(),
        );
    }
}
