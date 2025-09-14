<?php

namespace App\Controller\WorkoutGeneration;

use App\Entity\Workout\BodyPart;
use App\Entity\Workout\Enum\WorkoutMovementGenerationTypeEnum;
use App\Entity\Workout\Enum\WorkoutTypeEnum;
use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\WorkoutType;
use App\Repository\Workout\MovementRepository;
use App\Repository\WorkoutGeneration\WorkoutGenerationRepository;
use App\Services\Workout\MovementDifficultyService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WorkoutGenerationStep2 extends AbstractController
{

    public function __construct(
        private readonly WorkoutGenerationRepository $workoutGenerationRepository,
        private readonly MovementRepository $movementRepository,
        private readonly MovementDifficultyService $movementDifficultyService,
    )
    {
    }

    #[Route('/workout-generator-step-2', name: 'workout-generator-step-2')]
    public function new(Request $request): Response
    {
        $workoutGenerationId = $request->query->get('workout_generation_id') ?? null;
        if ($workoutGenerationId === null) {
            $this->addFlash('error', 'No workout generation ID provided. Please start from the beginning.');
            return $this->redirectToRoute('workout-generator');
        }

        $workoutGeneration = $this->workoutGenerationRepository->find($workoutGenerationId);
        $difficultiesEntitiesToGet = $this->movementDifficultyService->getWorkoutDifficultiesFromOne($workoutGeneration->getMovementDifficulty());
        $compatibleMovements = $this->movementRepository->getMovementsByMovementTypesAndDifficulty($workoutGeneration->getMovementTypes()->toArray(), $difficultiesEntitiesToGet);
        $form = $this->getForm($compatibleMovements, $workoutGeneration->getMovementGenerationType(), $workoutGeneration->getWorkoutType());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $errors = $form->getErrors();

            if ($errors->count() > 0) {
                $this->addFlash('error', 'There were errors in your form submission.');
//                return $this->redirectToRoute('workout-generator');
            }

            $workoutName = $data['workout_name'];
            $timeCap = $data['timecap_in_seconds'];
            $workoutType = $data['workout_type'];
            $movementTypes = $data['movement_types'];
            $numberOfDifferentMovements = $data['number_of_different_movements'];
            $selectedMovements = $data['required_movements'];
            $bannedMovements = $data['banned_movements'];
            $selectedImplements = $data['implements_you_have'];
            $bodyParts = $data['workout_for_the'];

            dd(
                $workoutName,
                $movementTypes,
                $workoutType,
                $numberOfDifferentMovements,
                $timeCap,
                $selectedMovements,
                $bannedMovements,
                $selectedImplements,
                $bodyParts,
            );

//            $this->workoutGeneratorService->generateWorkout(
//                $workoutName,
//                $movementTypes,
//                $workoutType,
//                $numberOfDifferentMovements,
//                $timeCap,
//                $selectedMovements,
//                $bannedMovements,
//                $selectedImplements,
//            );
        }

        return $this->render('admin/movementGeneratorStep2.html.twig', [
            'form' => $form->createView(),
            'workout_generation' => $workoutGeneration,
        ]);
    }

    private function getForm(
        array $compatibleMovements,
        WorkoutMovementGenerationTypeEnum $buildWorkoutMovementsFrom,
        WorkoutType $workoutType,
    ) : FormInterface
    {
        $form = $this->createFormBuilder()
            ->add('banned_movements', EntityType::class, [
                'label' => 'Movements you do NOT want in your workout',
                'class' => Movement::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'label_attr' => [
                    'class' => 'checkbox-switch',
                ],
                'choices' => $compatibleMovements,
            ])
            ->add('implements_you_have', EntityType::class, [
                'label' => 'Implements that you have available',
                'class' => Implement::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'label_attr' => [
                    'class' => 'checkbox-switch',
                ],
            ]);

        if($buildWorkoutMovementsFrom === WorkoutMovementGenerationTypeEnum::BODY_PART) {
            $form
                ->add('workout_for_the', EntityType::class, [
                    'class' => BodyPart::class,
                    'choice_label' => 'name',
                    'multiple' => true,
                    'expanded' => true,
                ]);
        }
        else{
            $form
                ->add('required_movements', EntityType::class, [
                    'class' => Movement::class,
                    'choice_label' => 'name',
                    'multiple' => true,
                    'expanded' => true,
                    'label_attr' => [
                        'class' => 'checkbox-switch',
                    ],
                    'choices' => $compatibleMovements,
                ]);
        }
        if($workoutType->getNameAsEnum() === WorkoutTypeEnum::INTERVALS) {
            $form
                ->add('intervals_time', IntegerType::class, [
                    'label' => 'Interval time (in seconds)',
                    'required' => true,
                    'attr' => [
                        'placeholder' => 'e.g. 30',
                    ],
                    'label_attr' => [
                        'class' => 'form-label',
                    ],
                ])
                ->add('intervals_rest_time', IntegerType::class, [
                    'label' => 'Rest time between intervals (in seconds, 0 for no rest)',
                    'required' => true,
                    'attr' => [
                        'placeholder' => 'e.g. 15',
                    ],
                    'label_attr' => [
                        'class' => 'form-label',
                    ],
                ]);
        }

        return $form->getForm();
    }
}