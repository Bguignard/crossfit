<?php

namespace App\Dto\Workout;

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
}
