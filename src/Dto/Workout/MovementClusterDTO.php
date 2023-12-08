<?php

namespace App\Dto\Workout;

use App\Entity\Workout\MovementCluster;
use App\Enum\ImplementEnum;
use App\Enum\RepUnitEnum;
use Symfony\Component\Uid\Uuid;

final readonly class MovementClusterDTO
{
    public function __construct(
        public ?Uuid $id,
        public int $repetitions,
        public RepUnitEnum $repUnit,
        /**
         * @var ImplementEnum[]
         */
        public array $implements,
        public MovementDTO $movement,
        public MovementDetailDTO $movementDetail,
    ) {
    }

    public static function createFromEntity(MovementCluster $movementCluster): self
    {
        return new self(
            $movementCluster->getId(),
            $movementCluster->getRepetitions(),
            $movementCluster->getRepUnit(),
            $movementCluster->getImplements()->toArray(),
            MovementDTO::createFromEntity($movementCluster->getMovement()),
            MovementDetailDTO::createFromEntity($movementCluster->getMovementDetail()),
        );
    }
}
