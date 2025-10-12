<?php

declare(strict_types=1);

namespace App\Controller\WorkoutGeneration;

use App\Repository\Workout\SimpleWorkoutRepositoryInterface;
use App\Repository\WorkoutGeneration\WorkoutGenerationRepositoryInterface;
use App\Services\Workout\SimpleWorkoutCreatorServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SimpleWorkoutGeneratorController extends AbstractController
{
    public function __construct(
        private readonly SimpleWorkoutCreatorServiceInterface $simpleWorkoutCreator,
        private readonly WorkoutGenerationRepositoryInterface $workoutGenerationRepository,
        private readonly SimpleWorkoutRepositoryInterface $simpleWorkoutRepository,
    ) {
    }

    #[Route('/api/simple-workout-generator/{id}', name: 'simple-workout-generator', requirements: ['id' => '[0-9a-fA-F\-]{36}'])]
    public function index(string $id): Response
    {
        $workoutGeneration = $this->workoutGenerationRepository->find($id);
        if (!$workoutGeneration) {
            return $this->json(['error' => 'Workout generation not found'], Response::HTTP_NOT_FOUND);
        }
        $simpleWorkout = $this->simpleWorkoutCreator->createSimpleWorkout($workoutGeneration);
        $this->simpleWorkoutRepository->persist($simpleWorkout);

        return $this->json($simpleWorkout->getId()->toString());
    }
}
