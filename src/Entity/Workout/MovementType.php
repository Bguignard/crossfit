<?php

namespace App\Entity\Workout;

enum TypeOfMovement: string
{
    case GYMNASTIC = "Gymnastic";
    case WEIGHTLIFTING = "Weightlifting";
    case CARDIO = "Cardio";
    case STRONGMAN = "Strongman";
}