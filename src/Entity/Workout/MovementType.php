<?php

namespace App\Entity\Workout;

use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\Mapping as ORM;

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
