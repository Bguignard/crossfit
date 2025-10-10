<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\DataProvider\WorkoutGenerationPossibleMovementsProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Uuid;

#[ApiResource(provider: WorkoutGenerationPossibleMovementsProvider::class,
    operations: [
        new Get(
            uriTemplate: '/workout_generation_possible_movements/{workoutGenerationId}'
        ),
    ])]
class WorkoutGenerationPossibleMovementsDto
{
    public function __construct(
        public Uuid $workoutGenerationId,
        public Collection $possibleMovements = new ArrayCollection(),
    ) {
    }
}
