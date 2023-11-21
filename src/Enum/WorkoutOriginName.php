<?php

namespace App\Enum;

enum WorkoutOriginName: string
{
    case CROSSFIT_GAMES = 'CrossFit Games';
    case CROSSFIT_REGIONALS = 'CrossFit Regionals';
    case CROSSFIT_SEMIFINALS = 'CrossFit Semifinals';
    case CROSSFIT_QUARTERFINALS = 'CrossFit Quarterfinals';
    case CROSSFIT_OPEN_WORKOUT = 'CrossFit open workout';
    case GIRLS_WORKOUT = 'Girls workout';
    case HERO_WORKOUT = 'Hero workout';
    case SANCTIONAL_EVENTS = 'Sanctional_events';
    case OTHER = 'Other';
}
