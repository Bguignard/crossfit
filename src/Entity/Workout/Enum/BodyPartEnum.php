<?php

namespace App\Entity\Workout\Enum;

enum BodyPartEnum: string
{
    case LEGS = 'legs';
    case LOWER_BACK = 'lower back';
    case UPPER_BACK = 'upper back';
    case SHOULDERS = 'shoulders';
    case ARMS = 'arms';
    case FOREARMS = 'forearms';
    case ABS = 'abs';
    case CHEST = 'chest';
    case GLUTES = 'glutes';
}
