<?php

namespace App\Controller;

use App\Repository\Workout\BlockRepository;
use App\Repository\Workout\BlockRepositoryInterface;
use App\Repository\Workout\BodyPartRepository;
use App\Repository\Workout\BodyPartRepositoryInterface;
use App\Repository\Workout\ImplementRepository;
use App\Repository\Workout\ImplementRepositoryInterface;
use App\Repository\Workout\MovementClusterRepository;
use App\Repository\Workout\MovementClusterRepositoryInterface;
use App\Repository\Workout\MovementRepository;
use App\Repository\Workout\MovementRepositoryInterface;
use App\Repository\Workout\MovementTypeRepository;
use App\Repository\Workout\MovementTypeRepositoryInterface;
use App\Repository\Workout\WorkoutOriginRepositoryInterface;
use App\Repository\Workout\WorkoutRepository;
use App\Repository\Workout\WorkoutRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class FixturesController extends AbstractController
{
    public function __construct(
        public readonly BlockRepositoryInterface $blockRepository,
        public readonly BodyPartRepositoryInterface $bodyPartRepository,
        public readonly ImplementRepositoryInterface $implementRepository,
        public readonly MovementClusterRepositoryInterface $movementClusterRepository,
        public readonly MovementRepositoryInterface $movementRepository,
        public readonly MovementTypeRepositoryInterface $movementTypeRepository,
        public readonly WorkoutRepositoryInterface $workoutRepository,
        public readonly WorkoutOriginRepositoryInterface $workoutOriginRepository,
    ) {
    }

    public function __invoke(): Response
    {
        return $this->render('fixtures/index.html.twig', [
            'workoutOrigins' => $this->workoutOriginRepository->findAll(),
            'workouts' => $this->workoutRepository->findAll(),
        ]);
    }
}
