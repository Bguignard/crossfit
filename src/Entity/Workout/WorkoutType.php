<?php

namespace App\Entity\Workout;

enum WorkoutType: string
{
    case ForTime = 'For time';
    case AMRAP = 'AMRAP';
    case ForWeight = 'For weight';
}
