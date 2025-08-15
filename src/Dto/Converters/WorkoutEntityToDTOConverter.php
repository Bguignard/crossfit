<?php

namespace App\Dto\Converters;

use App\Dto\Workout\WorkoutDTO;
use App\Entity\ConvertibleToDTOInterface;
use App\Entity\Workout\Workout;

class WorkoutEntityToDTOConverter implements EntityToDTOConverterInterface
{
    public static function createFromEntity(ConvertibleToDTOInterface $workout): WorkoutDTO
    {
        if (!($workout instanceof Workout)) {
            throw new \InvalidArgumentException(sprintf('Entity must be of type % ', Workout::class));
        }

        $blocks = [];
        foreach ($workout->getBlocks() as $block) {
            $blocks[] = BlockEntityToDTOConverter::createFromEntity($block);
        }

        return new WorkoutDTO(
            $workout->getId(),
            $workout->getName(),
            $workout->getNumberOfRounds(),
            $blocks,
            $workout->getTimeCap(),
            $workout->getWorkoutType(),
            WorkoutOriginEntityToDTOConverter::createFromEntity($workout->getWorkoutOrigin()),
        );
    }
}
