<?php

namespace App\Dto\Converters;

use App\Dto\Workout\WorkoutOriginDTO;
use App\Entity\ConvertibleToDTOInterface;
use App\Entity\Workout\WorkoutOrigin;

class WorkoutOriginEntityToDTOConverter implements EntityToDTOConverterInterface
{
    public static function createFromEntity(ConvertibleToDTOInterface $workoutOrigin): WorkoutOriginDTO
    {
        if (!($workoutOrigin instanceof WorkoutOrigin)) {
            throw new \InvalidArgumentException(sprintf('Entity must be of type % ', WorkoutOrigin::class));
        }

        return new WorkoutOriginDTO(
            $workoutOrigin->getId(),
            $workoutOrigin->getName()->getNameAsEnum(),
            $workoutOrigin->getYear(),
        );
    }
}
