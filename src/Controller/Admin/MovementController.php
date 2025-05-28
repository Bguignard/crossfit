<?php

namespace App\Controller\Admin;

use App\Dto\Workout\ImplementDTO;
use App\Dto\Workout\MovementDTO;
use App\Entity\Workout\Enum\MovementTypeEnum;
use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Repository\Workout\ImplementRepositoryInterface;
use App\Repository\Workout\MovementRepositoryInterface;
use App\Services\Workout\MovementGeneratorServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MovementController extends AbstractController
{
    public function __construct(
        private readonly ImplementRepositoryInterface $implementRepository,
        private readonly MovementRepositoryInterface $movementRepository,
        private readonly MovementGeneratorServiceInterface $movementGeneratorService,
    ) {
    }

    #[Route('/movement_generator', name: 'movement_generator')]
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
            $maxDifficulty = (int) $request->request->get('maxDifficulty') ?? null;
            $availableImplementsIds = $request->request->get('availableImplements') ?? null;
            $forbiddenMovementsIds = $request->request->get('forbiddenMovements') ?? null;

            $availableImplements = [$this->implementRepository->find($availableImplementsIds)];
            $forbiddenMovements = [$this->movementRepository->find($forbiddenMovementsIds)];

            $generatedMovement = $this->movementGeneratorService->generateMovement(
                $availableImplements,
                $maxDifficulty,
                $forbiddenMovements,
                $movementType
            );
            $generatedMovement = MovementDTO::createFromEntity($generatedMovement);
        }

        return $this->render('admin/movementGenerator.html.twig',
            [
                'postAddress' => $this->generateUrl('movement_generator'),
                'implements' => $implements,
                'movements' => $movements,
                'movementTypes' => array_column(MovementTypeEnum::cases(), 'value'),
                'generatedMovement' => $generatedMovement,
            ]);
    }
}
