<?php

namespace App\Entity\Workout\Enum;

enum MovementDifficultyEnum: string
{
    case BEGINNER = 'Beginner';
    case INTERMEDIATE = 'Intermediate';
    case RX = 'RX';
    case ELITE = 'Elite';
}
