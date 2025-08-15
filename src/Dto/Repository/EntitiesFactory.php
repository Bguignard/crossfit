<?php

namespace App\Dto\Repository;

use App\Dto\Converters\EntityToDTOConverterInterface;
use App\Dto\DTOFromEntityInterface;
use App\Entity\ConvertibleToDTOInterface;

class EntitiesFactory
{
    /**
     * @param ConvertibleToDTOInterface[] $entitiesToConvert
     *
     * @return DTOFromEntityInterface[]
     */
    public function createAllFromEntity(EntityToDTOConverterInterface $entityToDTOConverter, array $entitiesToConvert): array
    {
        $dtos = [];
        foreach ($entitiesToConvert as $entity) {
            $dtos[] = $entityToDTOConverter::createFromEntity($entity);
        }

        return $dtos;
    }
}
