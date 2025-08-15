<?php

namespace App\Dto\Workout;

use App\Dto\DTOFromEntityInterface;
use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use Symfony\Component\Uid\Uuid;

final readonly class WorkoutOriginDTO implements DTOFromEntityInterface
{
    public function __construct(
        public ?Uuid $id,
        public ?WorkoutOriginNameEnum $name,
        public ?int $year,
    ) {
    }
}
