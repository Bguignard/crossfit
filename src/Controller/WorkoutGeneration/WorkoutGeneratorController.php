<?php

declare(strict_types=1);

namespace App\Controller\WorkoutGeneration;

use App\Repository\Workout\WorkoutRepositoryInterface;
use App\Repository\WorkoutGeneration\WorkoutGenerationRepositoryInterface;
use App\Services\Workout\WorkoutCreatorServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class WorkoutGeneratorController extends AbstractController
{
    public function __construct(
        private readonly WorkoutCreatorServiceInterface $workoutCreator,
        private readonly WorkoutGenerationRepositoryInterface $workoutGenerationRepository,
        private readonly WorkoutRepositoryInterface $workoutRepository,
    ) {
    }

    #[Route('/api/workout-generator/{id}', name: 'workout-generator', requirements: ['id' => '[0-9a-fA-F\-]{36}'], methods: ['POST'])]
    #[Route('/api/simple-workout-generator/{id}', name: 'simple-workout-generator', requirements: ['id' => '[0-9a-fA-F\-]{36}'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function index(string $id): Response
    {
        $workoutGeneration = $this->workoutGenerationRepository->find($id);
        if (!$workoutGeneration) {
            return $this->json(['error' => 'Workout generation not found'], Response::HTTP_NOT_FOUND);
        }
        $workout = $this->workoutCreator->createWorkout($workoutGeneration);
        $this->workoutRepository->persist($workout);

        return $this->json([
            'id' => $workout->getId()->toString(),
            'name' => $workout->getName(),
            'flow' => $workout->getFlow(),
            'timeCap' => $workout->getTimeCap(),
            'numberOfRounds' => $workout->getNumberOfRounds(),
            'workoutType' => $workout->getWorkoutType()?->getName(),
        ]);
    }
}
