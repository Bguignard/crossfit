<?php

namespace App\Dto\Workout;

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
}
