<?php

namespace App\Services\Workout;

interface MovementTypeServiceInterface
{
    public function getMovementTypesEntitiesFromIds(array $movementTypes): array;
}
