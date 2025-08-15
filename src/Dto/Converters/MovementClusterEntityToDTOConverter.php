<?php

namespace App\Dto\Converters;

use App\Dto\Workout\MovementClusterDTO;
use App\Dto\Workout\MovementDTO;
use App\Entity\ConvertibleToDTOInterface;
use App\Entity\Workout\MovementCluster;

class MovementClusterEntityToDTOConverter implements EntityToDTOConverterInterface
{
    public static function createFromEntity(ConvertibleToDTOInterface $movementCluster): MovementClusterDTO
    {
        if (!($movementCluster instanceof MovementCluster)) {
            throw new \InvalidArgumentException(sprintf('Entity must be of type % ', MovementCluster::class));
        }

        return new MovementClusterDTO(
            $movementCluster->getId(),
            $movementCluster->getRepetitions(),
            $movementCluster->getRepUnit(),
            MovementDTO::createFromEntity($movementCluster->getMovement()),
            $movementCluster->getImplementIntensityAdjustmentValue(),
            $movementCluster->getImplementIntensityUnit(),
            $movementCluster->getImplements()->toArray(),
        );
    }
}
