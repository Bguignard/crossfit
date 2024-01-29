<?php

namespace App\Entity\Workout\Enum;

enum WorkoutTypeEnum: string
{
    case FOR_TIME = 'For time';
    case AMRAP = 'AMRAP';
    case FOR_WEIGHT = 'For weight';
    case EMOM = 'EMOM';
    case E2MOM = 'E2MOM';
}
