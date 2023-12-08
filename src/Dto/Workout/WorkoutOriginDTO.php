<?php

namespace App\Dto\Workout;

use App\Entity\Workout\WorkoutOrigin;
use App\Enum\WorkoutOriginNameEnum;
use Symfony\Component\Uid\Uuid;

final readonly class WorkoutOriginDTO
{
    public function __construct(
        public ?Uuid $id,
        public ?WorkoutOriginNameEnum $name,
        public ?int $year,
    ) {
    }

    public static function createFromEntity(WorkoutOrigin $workoutOrigin): self
    {
        return new self(
            $workoutOrigin->getId(),
            $workoutOrigin->getName(),
            $workoutOrigin->getYear(),
        );
    }
}
