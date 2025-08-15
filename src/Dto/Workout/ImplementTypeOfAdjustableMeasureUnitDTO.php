<?php

namespace App\Dto\Workout;

use App\Dto\DTOFromEntityInterface;
use App\Entity\Workout\Enum\ImplementTypeOfMeasureEnum;
use Symfony\Component\Uid\Uuid;

final readonly class ImplementTypeOfAdjustableMeasureUnitDTO implements DTOFromEntityInterface
{
    public function __construct(
        public ?Uuid $id,
        public ImplementTypeOfMeasureEnum $implementTypeOfMeasureEnum,
        public array $measureUnits,
    ) {
    }
}
