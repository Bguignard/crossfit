<?php

namespace App\Entity\Workout\Enum;

enum BodyPartEnum: string
{
    case PECTORALS = 'pectorals';
    case LOWER_BACK = 'lower back';
    case SHOULDERS = 'shoulders';
    case BICEPS = 'biceps';
    case TRICEPS = 'triceps';
    case LEGS = 'legs';
    case ABDOMINALS = 'abdominals';
    case GLUTES = 'glutes';
    case HAMSTRINGS = 'hamstrings';
    case QUADRICEPS = 'quadriceps';
    case TRAPEZIUS = 'trapezius';
    case CALVES = 'calves';
    case RHOMBOIDS = 'rhomboids';
    case FOREARMS = 'forearms';
    case LATISSIMUS_DORSI = 'latissimus dorsi';
    case HIP_FLEXORS = 'hip flexors';
}
