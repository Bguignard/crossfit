<?php

namespace App\Services\Workout;

readonly class MuscleService implements MuscleServiceInterface
{
    public function getMusclesFromBodyParts(array $bodyParts): array
    {
        $muscles = [];
        foreach ($bodyParts as $bodyPart) {
            foreach ($bodyPart->getMuscles() as $muscle) {
                $muscles[$muscle->getId()->toString()] = $muscle;
            }
        }

        return array_values($muscles);
    }
}
