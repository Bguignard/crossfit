<?php

namespace App\Dto\Workout;

use App\Dto\DTOFromEntityInterface;
use App\Entity\Workout\Enum\MeasureUnitEnum;
use App\Entity\Workout\Implement;
use Symfony\Component\Uid\Uuid;

final readonly class MovementClusterDTO implements DTOFromEntityInterface
{
    public function __construct(
        public ?Uuid $id,
        public int $repetitions,
        public MeasureUnitEnum $repUnit,
        public MovementDTO $movement,
        public ?float $implementIntensityAdjustmentValue,
        public MeasureUnitEnum $implementIntensityUnit,
        /**
         * @var Implement[]
         */
        public array $implements,
    ) {
    }
}
