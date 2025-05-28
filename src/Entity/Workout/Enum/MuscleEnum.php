<?php

namespace App\Entity\Workout\Enum;

enum MuscleEnum: string
{
    case TRAPEZIUS = 'trapezius';
    case DELTOIDS = 'deltoids';
    case RHOMBOIDS = 'rhomboids';
    case BICEPS = 'biceps';
    case TRICEPS = 'triceps';
    case FOREARMS = 'forearms';
    case PECTORALS = 'pectorals';
    case RECTUS_ABDOMINIS = 'rectus abdominis';
    case OBLIQUES = 'obliques';
    case TRANSVERSUS_ABDOMINIS = 'transversus abdominis';
    case HIP_FLEXORS = 'hip flexors';
    case LATISSIMUS_DORSI = 'latissimus dorsi';
    case SPINAL_ERECTORS = 'spinal erectors';
    case GLUTEUS_MAXIMUS = 'gluteus maximus';
    case GLUTEUS_MEDIUS = 'gluteus medius';
    case HAMSTRINGS = 'hamstrings';
    case QUADRICEPS = 'quadriceps';
    case CALVES = 'calves';
}
