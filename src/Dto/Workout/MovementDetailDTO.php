<?php

namespace App\Dto\Workout;

use App\Entity\Workout\MovementDetail;
use App\Enum\RepUnitEnum;
use Symfony\Component\Uid\Uuid;

final readonly class MovementDetailDTO
{
    public function __construct(
        public ?Uuid $id,
        public ?float $movementIntensity,
        public ?RepUnitEnum $repUnit,
    ) {
    }

    public static function createFromEntity(MovementDetail $movementDetail): self
    {
        return new self(
            $movementDetail->getId(),
            $movementDetail->getMovementIntensity(),
            $movementDetail->getMovementIntensityUnit(),
        );
    }
}
