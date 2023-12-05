<?php

namespace App\Enum;

enum WorkoutTypeEnum: string
{
    case FOR_TIME = 'For time';
    case AMRAP = 'AMRAP';
    case FOR_WEIGHT = 'For weight';
}
