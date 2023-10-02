<?php

namespace App\Workout\Entity;

enum WorkoutType: string
{
    case ForTime = "For time";
    case AMRAP = "AMRAP";
    case ForWeight = "For weight";

}
