<?php

namespace App\Controller\Admin;

use App\Dto\Workout\ImplementDTO;
use App\Dto\Workout\MovementDTO;
use App\Entity\Workout\Enum\MovementTypeEnum;
use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Repository\Workout\ImplementRepositoryInterface;
use App\Repository\Workout\MovementRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WorkoutController extends AbstractController
{
    public function __construct(
        private readonly ImplementRepositoryInterface $implementRepository,
        private readonly MovementRepositoryInterface $movementRepository,
    ) {
    }

    #[Route('/workout-generator', name: 'workout-generator')]
    public function __invoke(Request $request): Response
    {
        $generatedMovement = null;
        $implements = array_map(
            fn (Implement $implement) => ImplementDTO::createFromEntity($implement),
            $this->implementRepository->findAll()
        );
        $movements = array_map(
            fn (Movement $movement) => MovementDTO::createFromEntity($movement),
            $this->movementRepository->findAll()
        );

        if ($request->isMethod('POST')) {
            $movementType = MovementTypeEnum::from($request->request->get('movementType'));
            $maxDifficulty = $request->request->get('maxDifficulty') ?? null;
            $availableImplementsIds = $request->request->get('availableImplements') ?? null;
            $forbiddenMovementsIds = $request->request->get('forbiddenMovements') ?? null;

            $availableImplements = [];
            $forbiddenMovements = [];

            if ($maxDifficulty === null) {
                throw new \InvalidArgumentException('Max difficulty is required');
            } else {
                $maxDifficulty = (int) $maxDifficulty;
            }

            if ($availableImplementsIds === null) {
                throw new \InvalidArgumentException('Available implements are required');
            } else {
                foreach ($availableImplementsIds as $availableImplementsId) {
                    $availableImplements[] = $this->implementRepository->find($availableImplementsId);
                }
            }

            if ($forbiddenMovementsIds === null) {
                throw new \InvalidArgumentException('Forbidden movements are required');
            } else {
                foreach ($forbiddenMovementsIds as $forbiddenMovementsId) {
                    $forbiddenMovements[] = $this->movementRepository->find($forbiddenMovementsId);
                }
            }

            $generatedMovement = $this->movementRepository->getMovementByDifficultyAndImplementsAndForbiddenMovementsAndType(
                $availableImplements,
                $maxDifficulty,
                $forbiddenMovements,
                $movementType
            );
            $generatedMovement = MovementDTO::createFromEntity($generatedMovement);
        }

        return $this->render('admin/movementGenerator.html.twig',
            [
                'postAddress' => $this->generateUrl('workout-generator'),
                'implements' => $implements,
                'movements' => $movements,
                'movementTypes' => array_column(MovementTypeEnum::cases(), 'value'),
                'generatedMovement' => $generatedMovement,
            ]);
    }
}
