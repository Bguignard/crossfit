<?php

namespace App\Services\Workout;

use App\Repository\Workout\MovementTypeRepository;

readonly class MovementTypeService implements MovementTypeServiceInterface
{
    public function __construct(
        public MovementTypeRepository $movementTypeRepository,
    ) {
    }

    public function getMovementTypesEntitiesFromIds(array $movementTypes): array
    {
        $movementTypesEntities = [];
        foreach ($movementTypes as $movementType) {
            $movementTypesEntities[] = $this->movementTypeRepository->findBy(['id' => $movementType]);
        }

        return $movementTypesEntities;
    }
}
