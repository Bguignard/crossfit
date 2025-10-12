<?php

namespace App\Services\Workout;

use App\Repository\Workout\MovementTypeRepositoryInterface;

readonly class MovementTypeService implements MovementTypeServiceInterface
{
    public function __construct(
        public MovementTypeRepositoryInterface $movementTypeRepository,
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
