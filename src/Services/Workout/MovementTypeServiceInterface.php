<?php

namespace App\Services\Workout;

use App\Repository\Workout\MovementTypeRepository;
interface MovementTypeServiceInterface
{
    public function getMovementTypesEntitiesFromIds(array $movementTypes): array;
}
