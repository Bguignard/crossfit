<?php

namespace App\Dto\Workout;

use App\Enum\BodyPartEnum;
use App\Enum\MovementTypeEnum;
use Symfony\Component\Uid\Uuid;

final readonly class MovementDTO
{
    public function __construct(
        public ?Uuid $id,
        public string $name,
        public int $difficulty,
        /**
         * @var BodyPartEnum[]
         */
        public array $bodyParts,
        public MovementTypeEnum $movementTypeEnum,
    ) {
    }
}
