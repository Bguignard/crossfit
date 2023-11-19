<?php

namespace App\Enum;

enum MovementType: string
{
    case GYMNASTIC = 'Gymnastic';
    case WEIGHTLIFTING = 'Weightlifting';
    case CARDIO = 'Cardio';
    case STRONGMAN = 'Strongman';
    case BODYBUILDING = 'Bodybuilding';
    case PLYOMETRIC = 'Plyometric';
    case WARM_UP = 'Warm-up';
    case STRETCHING = 'Stretching';
}
