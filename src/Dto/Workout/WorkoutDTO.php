<?php

namespace App\Dto\Workout;

use App\Dto\DTOFromEntityInterface;
use App\Entity\Workout\Enum\WorkoutTypeEnum;
use Symfony\Component\Uid\Uuid;

final readonly class WorkoutDTO implements DTOFromEntityInterface
{
    public function __construct(
        public ?Uuid $id,
        public ?string $name,
        public ?int $numberOfRounds,
        /**
         * @var BlockDTO[]
         */
        public array $blocks,
        public ?int $timeCap,
        public ?WorkoutTypeEnum $workoutType,
        public ?WorkoutOriginDTO $workoutOrigin,
    ) {
    }
}
