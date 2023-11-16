<?php

namespace App\Entities\Workout;

enum WorkoutType: string
{
    case ForTime = "For time";
    case AMRAP = "AMRAP";
    case ForWeight = "For weight";

}
