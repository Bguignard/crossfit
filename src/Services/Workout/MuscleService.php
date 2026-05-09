<?php

namespace App\Services\Workout;

use App\Entity\Workout\BodyPart;
use App\Entity\Workout\Muscle;

readonly class MuscleService implements MuscleServiceInterface
{
    /**
     * @param BodyPart[] $bodyParts
     *
     * @return Muscle[]
     */
    public function getMusclesFromBodyParts(array $bodyParts): array
    {
        if (count($bodyParts) === 0) {
            return [];
        }

        $muscles = [];
        foreach ($bodyParts as $bodyPart) {
            foreach ($bodyPart->getMuscles() as $muscle) {
                $muscles[$muscle->getId()->toString()] = $muscle;
            }
        }

        return array_values($muscles);
    }
}
