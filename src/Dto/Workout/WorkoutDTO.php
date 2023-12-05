<?php

namespace App\Dto\Workout;

use App\Enum\WorkoutTypeEnum;
use Symfony\Component\Uid\Uuid;

final readonly class WorkoutDTO
{
    public ?Uuid $id;
    public ?string $name;
    public ?int $numberOfRounds;
    /**
     * @var BlockDTO[]
     */
    public array $blocks;
    public ?int $timeCap;
    public ?WorkoutTypeEnum $workoutType;
    public ?WorkoutOriginDTO $workoutOrigin;
}
