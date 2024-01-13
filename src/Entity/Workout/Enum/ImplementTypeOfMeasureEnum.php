<?php

namespace App\Entity\Workout\Enum;

enum ImplementTypeOfMeasureEnum: string
{
    case WEIGHT = 'Weight';
    case DISTANCE = 'Distance';
    case HEIGHT = 'Height';
    case ENERGY = 'Energy';
    case PERCENTAGE_OF_1_RM = 'Percentage_of_1_rm';
    case RESISTANCE = 'Resistance';
    case DIFFICULTY = 'Difficulty';
}
