<?php

namespace App\Dto\Workout;

use App\Entity\Workout\Enum\BodyPartEnum;
use App\Entity\Workout\Enum\MovementTypeEnum;
use App\Entity\Workout\Movement;
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
        public MovementTypeEnum $movementType,
    ) {
    }

    public static function createFromEntity(Movement $movement): self
    {
        return new self(
            $movement->getId(),
            $movement->getName(),
            $movement->getDifficulty(),
            $movement->getMuscles()->toArray(),
            $movement->getMovementType(),
        );
    }
}
