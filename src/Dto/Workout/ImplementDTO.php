<?php

namespace App\Dto\Workout;

use App\Entity\Workout\Implement;

final readonly class ImplementDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public ?ImplementTypeOfAdjustableMeasureUnitDTO $implementTypeOfAdjustableMeasure,
    ) {
    }

    public static function createFromEntity(Implement $implement): self
    {
        return new self(
            (string) $implement->getId(),
            $implement->getName(),
            $implement->getImplementTypeOfAdjustableMeasure() ? ImplementTypeOfAdjustableMeasureUnitDTO::createFromEntity($implement->getImplementTypeOfAdjustableMeasure()) : null,
        );
    }
}
