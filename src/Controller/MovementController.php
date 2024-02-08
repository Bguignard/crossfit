<?php

namespace App\Controller;

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

class MovementController extends AbstractController
{
    public function __construct(
        private ImplementRepositoryInterface $implementRepository,
        private MovementRepositoryInterface $movementRepository,
        private MovementGeneratorServiceInterface $movementGeneratorService,
    ) {
    }

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
            $movementType ??= MovementTypeEnum::from($request->request->get('movementType'));
            $maxDifficulty ??= (int) $request->request->get('maxDifficulty');
            $availableImplementsIds ??= $request->request->get('availableImplements');
            $forbiddenMovementsIds ??= $request->request->get('forbiddenMovements');

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

        return $this->render('movementGenerator.html.twig',
            [
                'postAddress' => $this->generateUrl('movement_generator'),
                'implements' => $implements,
                'movements' => $movements,
                'movementTypes' => array_column(MovementTypeEnum::cases(), 'value'),
                'generatedMovement' => $generatedMovement,
        ]);
    }
}
