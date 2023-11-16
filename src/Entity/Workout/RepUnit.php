<?php

namespace App\Entity\Workout;

enum RepUnit: string
{
    case METER = 'meter';
    case REPETITION = 'repetition';
    case SECOND = 'second';
    case KILOGRAM = 'kilogram';
}
