<?php

namespace App\Dto\Workout;

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
}
