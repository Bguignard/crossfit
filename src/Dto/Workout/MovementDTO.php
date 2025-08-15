<?php

namespace App\Dto\Workout;

use App\Dto\DTOFromEntityInterface;
use App\Entity\Workout\Enum\BodyPartEnum;
use App\Entity\Workout\Enum\MovementTypeEnum;
use Symfony\Component\Uid\Uuid;

final readonly class MovementDTO implements DTOFromEntityInterface
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
}
