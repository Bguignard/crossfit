<?php

namespace App\Controller\Admin;

use App\Services\Workout\WorkoutGeneratorServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WorkoutController extends AbstractController
{
    public function __construct(
        private WorkoutGeneratorServiceInterface $workoutGeneratorService,
    ) {
    }

    #[Route('/workouts', name: 'workouts')]
    public function __invoke(Request $request): Response
    {
        // todo : this controller is made to view workouts
    }
}
