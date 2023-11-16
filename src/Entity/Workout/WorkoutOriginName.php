<?php

namespace App\Entity\Workout;

use App\Repository\Workout\WorkoutOriginNameRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkoutOriginNameRepository::class)]
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
