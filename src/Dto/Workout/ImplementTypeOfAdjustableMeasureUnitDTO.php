<?php

namespace App\Dto\Workout;

use App\Entity\Workout\Enum\ImplementTypeOfMeasureEnum;
use App\Entity\Workout\ImplementTypeOfAdjustableMeasureUnit;
use Symfony\Component\Uid\Uuid;

final readonly class ImplementTypeOfAdjustableMeasureUnitDTO
{
    public function __construct(
        public ?Uuid $id,
        public ImplementTypeOfMeasureEnum $implementTypeOfMeasureEnum,
        public array $measureUnits,
    ) {
    }

    public static function createFromEntity(ImplementTypeOfAdjustableMeasureUnit $implementTypeOfAdjustableMeasureUnit): self
    {
        return new self(
            $implementTypeOfAdjustableMeasureUnit->getId(),
            $implementTypeOfAdjustableMeasureUnit->getImplementTypeOfMeasureEnum(),
            $implementTypeOfAdjustableMeasureUnit->getMeasureUnits()->toArray(),
        );
    }
}
