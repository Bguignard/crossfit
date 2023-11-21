<?php

namespace App\Controller;

use App\Repository\Workout\BlockRepository;
use App\Repository\Workout\BodyPartRepository;
use App\Repository\Workout\ImplementRepository;
use App\Repository\Workout\MovementClusterRepository;
use App\Repository\Workout\MovementRepository;
use App\Repository\Workout\MovementTypeRepository;
use App\Repository\Workout\WorkoutRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class FixturesController extends AbstractController
{
    public function __construct(
        public readonly BlockRepository $blockRepository,
        public readonly BodyPartRepository $bodyPartRepository,
        public readonly ImplementRepository $implementRepository,
        public readonly MovementClusterRepository $movementClusterRepository,
        public readonly MovementRepository $movementRepository,
        public readonly MovementTypeRepository $movementTypeRepository,
        public readonly WorkoutRepository $workoutRepository,
    ) {
    }

    public function __invoke(): Response
    {
        return $this->render('fixtures/index.html.twig', [
            'workoutOrigins' => $this->workoutRepository->getWorkoutsOrigins(),
            'workouts' => $this->workoutRepository->findAll(),
        ]);
    }
}
