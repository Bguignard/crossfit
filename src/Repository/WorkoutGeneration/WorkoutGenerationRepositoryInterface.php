<?php

namespace App\Repository\WorkoutGeneration;

use App\Entity\WorkoutGeneration\WorkoutGeneration;

interface WorkoutGenerationRepositoryInterface
{
    public function save(WorkoutGeneration $workoutGeneration): WorkoutGeneration;
}
