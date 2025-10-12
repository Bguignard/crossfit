<?php

namespace App\Services\Workout;

interface MuscleServiceInterface
{
    public function getMusclesFromBodyParts(array $bodyParts): array;
}
