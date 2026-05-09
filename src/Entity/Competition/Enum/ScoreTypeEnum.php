<?php

namespace App\Entity\Competition\Enum;

enum ScoreTypeEnum: string
{
    case REPS = 'reps';
    case TIME = 'time';
    case LOAD = 'load';
    case DISTANCE = 'distance';
    case CALORIES = 'calories';
    case POINTS = 'points';
    case RAW = 'raw';
}
