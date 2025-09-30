<?php

namespace App\Entity\Workout\Enum;

enum WorkoutMovementGenerationTypeEnum: string
{
    case BODY_PART = 'body parts';
    case MOVEMENT = 'selected movements';
}
