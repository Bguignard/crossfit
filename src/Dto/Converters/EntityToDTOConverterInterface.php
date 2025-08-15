<?php

namespace App\Dto\Converters;

use App\Dto\DTOFromEntityInterface;
use App\Entity\ConvertibleToDTOInterface;

interface EntityToDTOConverterInterface
{
    public static function createFromEntity(ConvertibleToDTOInterface $object): DTOFromEntityInterface;
}
