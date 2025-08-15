<?php

namespace App\Dto\Workout;

use App\Dto\DTOFromEntityInterface;

final readonly class ImplementDTO implements DTOFromEntityInterface
{
    public function __construct(
        public string $id,
        public string $name,
        public ?ImplementTypeOfAdjustableMeasureUnitDTO $implementTypeOfAdjustableMeasure,
    ) {
    }
}
