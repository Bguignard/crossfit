<?php

namespace App\Dto\Converters;

use App\Dto\Workout\ImplementDTO;
use App\Entity\ConvertibleToDTOInterface;
use App\Entity\Workout\Implement;

class ImplementEntityToDTOConverter implements EntityToDTOConverterInterface
{
    public static function createFromEntity(ConvertibleToDTOInterface $implement): ImplementDTO
    {
        if (!($implement instanceof Implement)) {
            throw new \InvalidArgumentException(sprintf('Entity must be of type % ', Implement::class));
        }

        return new ImplementDTO(
            (string) $implement->getId(),
            $implement->getName(),
            $implement->getImplementTypeOfAdjustableMeasure() ? ImplementTypeOfAdjustableMeasureUnitEntityToDTOConverter::createFromEntity($implement->getImplementTypeOfAdjustableMeasure()) : null,
        );
    }
}
