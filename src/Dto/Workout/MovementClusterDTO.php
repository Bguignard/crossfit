<?php

namespace App\Dto\Workout;

use App\Entity\Workout\Enum\MeasureUnitEnum;
use App\Entity\Workout\Implement;
use App\Entity\Workout\MovementCluster;
use Symfony\Component\Uid\Uuid;

final readonly class MovementClusterDTO
{
    public function __construct(
        public ?Uuid $id,
        public int $repetitions,
        public MovementDTO $movement,
        public ?float $implementIntensityAdjustmentValue,
        public ?ImplementTypeOfAdjustableMeasureUnitDTO $movementDetailIntensityUnit,
        public MeasureUnitEnum $repUnit,
        /**
         * @var Implement[]
         */
        public array $implements,
    ) {
    }

    public static function createFromEntity(MovementCluster $movementCluster): self
    {
        return new self(
            $movementCluster->getId(),
            $movementCluster->getRepetitions(),
            MovementDTO::createFromEntity($movementCluster->getMovement()),
            $movementCluster->getImplementIntensityAdjustmentValue(),
            null === $movementCluster->getMovementDetailIntensityUnit() ? null : ImplementTypeOfAdjustableMeasureUnitDTO::createFromEntity($movementCluster->getMovementDetailIntensityUnit()),
            $movementCluster->getRepUnit(),
            $movementCluster->getImplements()->toArray(),
        );
    }
}
