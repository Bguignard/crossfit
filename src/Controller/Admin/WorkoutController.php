<?php

namespace App\Controller\Admin;

use App\Entity\Workout\BodyPart;
use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\MovementDifficulty;
use App\Entity\Workout\MovementType;
use App\Entity\Workout\WorkoutType;
use App\Services\Workout\WorkoutGeneratorServiceInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
