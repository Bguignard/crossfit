<?php

namespace App\Controller\Admin;

use App\Dto\Workout\ImplementDTO;
use App\Dto\Workout\MovementClusterDTO;
use App\Dto\Workout\MovementDTO;
use App\Entity\Workout\Enum\MeasureUnitEnum;
use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Repository\Workout\ImplementRepositoryInterface;
use App\Repository\Workout\MovementClusterRepositoryInterface;
use App\Repository\Workout\MovementRepositoryInterface;
use App\Services\Workout\MovementClusterGeneratorServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MovementClusterController extends AbstractController
{
    public function __construct(
        private readonly ImplementRepositoryInterface $implementRepository,
        private readonly MovementRepositoryInterface $movementRepository,
        private readonly MovementClusterRepositoryInterface $movementClusterRepository,
        private readonly MovementClusterGeneratorServiceInterface $movementClusterGeneratorService,
    ) {
    }

    #[Route('/movement-cluster-generator', name: 'movement_cluster_generator')]
    public function __invoke(Request $request): Response
    {
        $errors = [];
        $generatedMovementCluster = null;
        $implements = array_map(
            fn (Implement $implement) => ImplementDTO::createFromEntity($implement),
            $this->implementRepository->findAll()
        );
        $movements = array_map(
            fn (Movement $movement) => MovementDTO::createFromEntity($movement),
            $this->movementRepository->findAll()
        );

        if ($request->isMethod('POST')) {
            $selectedImplements = [];
            $movementId = $request->request->get('movement') ?? '';
            $allowedTimeInSeconds = (int) $request->request->get('allowedTimeInSeconds');
            $selectedImplementsIds = $request->request->get('implements') ?? null;
            $measureUnitOfMovement = MeasureUnitEnum::from($request->request->get('measureUnitOfMovement'));
            $measureUnitOfImplement = MeasureUnitEnum::from($request->request->get('measureUnitOfImplement'));
            $implementIntensityValue = (float) $request->request->get('implementIntensityValue');

            if ($movementId === '') {
                throw new \InvalidArgumentException('Movement is required');
            }
            if ($selectedImplementsIds !== null) {
                $selectedImplements = $this->implementRepository->findBy(['id' => $selectedImplementsIds]);
            }

            $movement = $this->movementRepository->find($movementId);
            try {
                $generatedMovementCluster = $this->movementClusterGeneratorService->generateMovementCluster(
                    $movement,
                    $measureUnitOfMovement,
                    $allowedTimeInSeconds,
                    $selectedImplements,
                    $measureUnitOfImplement,
                    $implementIntensityValue
                );
                $this->movementClusterRepository->persist($generatedMovementCluster);
                $generatedMovementCluster = MovementClusterDTO::createFromEntity($generatedMovementCluster);
            } catch (\InvalidArgumentException $argumentException) {
                $errors[] = $argumentException->getMessage();
            }
        }

        return $this->render('admin/movementClusterGenerator.html.twig',
            [
                'errors' => $errors,
                'postAddress' => $this->generateUrl('movement_cluster_generator'),
                'implements' => $implements,
                'movements' => $movements,
                'measureUnits' => array_column(MeasureUnitEnum::cases(), 'value'),
                'generatedMovementCluster' => $generatedMovementCluster,
            ]);
    }
}
