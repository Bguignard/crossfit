<?php

namespace App\Controller\Workouts;

use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WorkoutsBySourceController extends AbstractController
{
    #[Route('/workouts/by-source', name: 'workouts_by_source')]
    public function __invoke(Request $request): Response
    {
        $sources = array_map(fn (WorkoutOriginNameEnum $case) => $case->value, WorkoutOriginNameEnum::cases());

        return $this->render('workouts/workouts-by-source.html.twig',
            [
                'workoutsSourceNames' => $sources,
            ]);
    }
}
