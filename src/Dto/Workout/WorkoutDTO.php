<?php

namespace App\Dto\Workout;

use App\Entity\Workout\Workout;
use App\Enum\WorkoutTypeEnum;
use Symfony\Component\Uid\Uuid;

final readonly class WorkoutDTO
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
    )
    {
    }

    public static function createFromEntity(Workout $workout): self
    {
        $blocks = [];
        foreach ($workout->getBlocks() as $block) {
            $blocks[] = BlockDTO::createFromEntity($block);
        }
        return new self(
            $workout->getId(),
            $workout->getName(),
            $workout->getNumberOfRounds(),
            $blocks,
            $workout->getTimeCap(),
            $workout->getWorkoutType(),
            WorkoutOriginDTO::createFromEntity($workout->getWorkoutOrigin()),
        );
    }
}

