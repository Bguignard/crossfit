<?php

namespace App\Dto\Converters;

use App\Dto\Workout\ImplementTypeOfAdjustableMeasureUnitDTO;
use App\Entity\ConvertibleToDTOInterface;
use App\Entity\Workout\ImplementTypeOfAdjustableMeasureUnit;

class ImplementTypeOfAdjustableMeasureUnitEntityToDTOConverter implements EntityToDTOConverterInterface
{
    public static function createFromEntity(ConvertibleToDTOInterface $implementTypeOfAdjustableMeasureUnit): ImplementTypeOfAdjustableMeasureUnitDTO
    {
        if (!($implementTypeOfAdjustableMeasureUnit instanceof ImplementTypeOfAdjustableMeasureUnit)) {
            throw new \InvalidArgumentException(sprintf('Entity must be of type % ', ImplementTypeOfAdjustableMeasureUnit::class));
        }

        return new ImplementTypeOfAdjustableMeasureUnitDTO(
            $implementTypeOfAdjustableMeasureUnit->getId(),
            $implementTypeOfAdjustableMeasureUnit->getImplementTypeOfMeasureEnum(),
            $implementTypeOfAdjustableMeasureUnit->getMeasureUnits()->toArray(),
        );
    }
}
