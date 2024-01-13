<?php

namespace App\Entity\Workout\Enum;

enum MeasureUnitEnum: string
{
    case METER = 'meter';
    case CENTIMETER = 'centimeter';
    case REPETITION = 'repetition';
    case SECOND = 'second';
    case KILOGRAM = 'kilogram';
    case CALORIE = 'calorie';
    case KILOMETER = 'kilometer';
    case MINUTE = 'minute';
    case HOUR = 'hour';
    case PERCENT = 'percent';
    case RPE = 'RPE';
}
