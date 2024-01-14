<?php

namespace App\Controller;

use App\Entity\Workout\Enum\MovementTypeEnum;
use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use App\Entity\Workout\Enum\WorkoutTypeEnum;
use App\Repository\Workout\BlockRepositoryInterface;
use App\Repository\Workout\BodyPartRepositoryInterface;
use App\Repository\Workout\ImplementRepositoryInterface;
use App\Repository\Workout\MovementClusterRepositoryInterface;
use App\Repository\Workout\MovementRepositoryInterface;
use App\Repository\Workout\MovementTypeRepositoryInterface;
use App\Repository\Workout\WorkoutOriginRepositoryInterface;
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
        return $this->render('fixtures.html.twig', [
            'workoutOriginsNames' => array_column(WorkoutOriginNameEnum::cases(), 'value'),
            'workouts' => $this->workoutRepository->findAll(),
            'movements' => $this->movementRepository->findAll(),
            'bodyParts' => $this->bodyPartRepository->findAll(),
            'movementTypes' => array_column(MovementTypeEnum::cases(), 'value'),
            'implements' => $this->implementRepository->findAll(),
            'workoutTypes' => array_column(WorkoutTypeEnum::cases(), 'value'),
        ]);
    }
}
