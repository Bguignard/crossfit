<?php

namespace App\Enum;

enum WorkoutType: string
{
    case ForTime = 'For time';
    case AMRAP = 'AMRAP';
    case ForWeight = 'For weight';
}
