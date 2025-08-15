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

    #[Route('/workout-generator', name: 'workout-generator')]
    public function __invoke(Request $request): Response
    {
        $generatedMovement = null;

        $form = $this->createFormBuilder()
            ->add(
                'workout_name',
                TextType::class,
                [
                    'label' => 'Name of your workout',
                    'required' => true,
                    'attr' => [
                        'placeholder' => 'My super workout',
                    ],
                    'label_attr' => [
                        'class' => 'form-label',
                    ],
                ])
            ->add(
                'timecap_in_seconds',
                IntegerType::class,
                [
                    'label' => 'Time Cap (in seconds)',
                    'required' => true,
                    'attr' => [
                        'placeholder' => '600 for 10 minutes',
                    ],
                    'label_attr' => [
                        'class' => 'form-label',
                    ],
                ])
            ->add(
                'movement_types', EntityType::class, [
                    'label' => 'What type of movements do you want in your workout?',
                    'class' => MovementType::class,
                    'choice_label' => 'name',
                    'multiple' => true,
                    'expanded' => true,
                ])
            ->add(
                'number_of_different_movements',
                IntegerType::class,
                [
                    'label' => 'How many different movements do you want in your workout?',
                    'required' => true,
                    'attr' => [
                        'placeholder' => 'Max 10 movements',
                    ],
                    'label_attr' => [
                        'class' => 'form-label',
                    ],
                ])
            ->add(
                'workout_type', EntityType::class, [
                    'class' => WorkoutType::class,
                    'choice_label' => 'name',
                    'multiple' => false,
                    'expanded' => true,
                ])
            ->add(
                'difficulty_of_movements', EntityType::class, [
                    'label' => 'Difficulty of your workout?',
                    'class' => MovementDifficulty::class,
                    'choice_label' => 'name',
                    'multiple' => false,
                    'expanded' => true,
                ])
            // END OF FIRST PAGE HERE : we need to have the difficulty of movements to display movements
                // AND we need to have the type of workout to display interval / rest time
            ->add('required_movements', EntityType::class, [
                'class' => Movement::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'label_attr' => [
                    'class' => 'checkbox-switch',
                ],
            ])
            ->add('banned_movements', EntityType::class, [
                // looks for choices from this entity
                'class' => Movement::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'label_attr' => [
                    'class' => 'checkbox-switch',
                ],
            ])
            ->add('implements_you_have', EntityType::class, [
                'class' => Implement::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'label_attr' => [
                    'class' => 'checkbox-switch',
                ],
            ])
            ->add('workout_for_the', EntityType::class, [
                'class' => BodyPart::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
            ])
            ->getForm();



        $form->handleRequest($request);




        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $errors = $form->getErrors();

            if ($errors->count() > 0) {
                $this->addFlash('error', 'There were errors in your form submission.');
                return $this->redirectToRoute('workout-generator');
            }



            $workoutName = $data['workout_name'];
            $timeCap = $data['timecap_in_seconds'];
            $workoutType = $data['workout_type'];
            $movementTypes = $data['movement_types'];
            $numberOfDifferentMovements = $data['number_of_different_movements'];
            $selectedMovements = $data['required_movements'];
            $bannedMovements = $data['banned_movements'];
            $selectedImplements = $data['implements_you_have'];
            $workoutDifficulty = $data['difficulty_of_movements'];
            $bodyParts = $data['workout_for_the'];

            $this->workoutGeneratorService->generateWorkout(
                $workoutName,
                $movementTypes,
                $workoutType,
                $numberOfDifferentMovements,
                $timeCap,
                $selectedMovements,
                $bannedMovements,
                $selectedImplements,
            );
        }

        return $this->render('admin/movementGenerator.html.twig', [
            'form' => $form->createView(),
        ]);

        //
        //        $implements = array_map(
        //            fn (Implement $implement) => ImplementDTO::createFromEntity($implement),
        //            $this->implementRepository->findAll()
        //        );
        //        $movements = array_map(
        //            fn (Movement $movement) => MovementDTO::createFromEntity($movement),
        //            $this->movementRepository->findAll()
        //        );
        //        if ($request->isMethod('POST')) {
        //            $movementType = MovementTypeEnum::from($request->request->get('movementType'));
        //            $maxDifficulty = $request->request->get('maxDifficulty') ?? null;
        //            $availableImplementsIds = $request->request->get('availableImplements') ?? null;
        //            $forbiddenMovementsIds = $request->request->get('forbiddenMovements') ?? null;
        //
        //            $availableImplements = [];
        //            $forbiddenMovements = [];
        //
        //            if ($maxDifficulty === null) {
        //                throw new \InvalidArgumentException('Max difficulty is required');
        //            } else {
        //                $maxDifficulty = (int) $maxDifficulty;
        //            }
        //
        //            if ($availableImplementsIds === null) {
        //                throw new \InvalidArgumentException('Available implements are required');
        //            } else {
        //                foreach ($availableImplementsIds as $availableImplementsId) {
        //                    $availableImplements[] = $this->implementRepository->find($availableImplementsId);
        //                }
        //            }
        //
        //            if ($forbiddenMovementsIds === null) {
        //                throw new \InvalidArgumentException('Forbidden movements are required');
        //            } else {
        //                foreach ($forbiddenMovementsIds as $forbiddenMovementsId) {
        //                    $forbiddenMovements[] = $this->movementRepository->find($forbiddenMovementsId);
        //                }
        //            }
        //
        //            $generatedMovement = $this->movementRepository->getMovementByDifficultyAndImplementsAndForbiddenMovementsAndType(
        //                $availableImplements,
        //                $maxDifficulty,
        //                $forbiddenMovements,
        //                $movementType
        //            );
        //            $generatedMovement = MovementDTO::createFromEntity($generatedMovement);
        //        }
        //
        //        return $this->render('admin/movementGenerator.html.twig',
        //            [
        //                'postAddress' => $this->generateUrl('workout-generator'),
        //                'implements' => $implements,
        //                'movements' => $movements,
        //                'movementTypes' => array_column(MovementTypeEnum::cases(), 'value'),
        //                'generatedMovement' => $generatedMovement,
        //            ]);
    }
}
