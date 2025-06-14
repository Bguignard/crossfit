<?php

namespace App\Entity\Workout\Enum;

enum WorkoutOriginNameEnum: string
{
    case CROSSFIT_GAMES = 'CrossFit Games';
    case CROSSFIT_REGIONALS = 'CrossFit Regionals';
    case CROSSFIT_SEMIFINALS = 'CrossFit Semifinals';
    case CROSSFIT_QUARTERFINALS = 'CrossFit Quarterfinals';
    case CROSSFIT_OPEN_WORKOUT = 'CrossFit open';
    case GIRLS_WORKOUT = 'Girls workout';
    case HERO_WORKOUT = 'Hero workout';
    case SANCTIONAL_EVENTS = 'Sanctional events';
    case OTHER = 'Other';
    case CUSTOM = 'Custom';
}
