<?php

namespace App\Controller\WorkoutGeneration;

use App\Entity\Workout\Enum\WorkoutMovementGenerationTypeEnum;
use App\Entity\Workout\MovementDifficulty;
use App\Entity\Workout\MovementType;
use App\Entity\Workout\WorkoutType;
use App\Entity\WorkoutGeneration\WorkoutGeneration;
use App\Repository\WorkoutGeneration\WorkoutGenerationRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WorkoutGenerationStep1 extends AbstractController
{
    public function __construct(
        private readonly WorkoutGenerationRepository $workoutGenerationRepository,
    ) {
    }

    #[Route('/workout-generator', name: 'workout-generator')]
    public function new(Request $request): Response
    {
        $dataToDisplay = [];
        $form = $this->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $errors = $form->getErrors();

            if ($errors->count() > 0) {
                $this->addFlash('error', 'There were errors in your form submission.');
            } else {
                $generatedWorkout = $this->generateNewWorkout($data);

                return $this->redirectToRoute('workout-generator-step-2', ['workout_generation_id' => $generatedWorkout->getId()->toString()]);
            }
        }

        return $this->render('admin/movementGenerator.html.twig', [
            'form' => $form->createView(),
            $dataToDisplay,
        ]);
    }

    private function getForm(): FormInterface
    {
        return $this->createFormBuilder()
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
            ->add('build_workout_movements_from', EnumType::class, [
                'label' => 'Build the workout from',
                'class' => WorkoutMovementGenerationTypeEnum::class,
                'choice_label' => 'value',
                'multiple' => false,
                'expanded' => true,
            ])
            ->getForm();
    }

    private function generateNewWorkout(array $data): WorkoutGeneration
    {
        $workoutName = $data['workout_name'];
        $timeCap = $data['timecap_in_seconds'];
        $workoutType = $data['workout_type'];
        $movementTypes = $data['movement_types'];
        $numberOfDifferentMovements = $data['number_of_different_movements'];
        $workoutDifficulty = $data['difficulty_of_movements'];
        $buildWorkoutMovementsFrom = $data['build_workout_movements_from'];

        $workoutGeneration = new WorkoutGeneration(
            $workoutName,
            $timeCap,
            $movementTypes->toArray(),
            $numberOfDifferentMovements,
            $workoutType,
            $workoutDifficulty,
            $buildWorkoutMovementsFrom
        );

        return $this->workoutGenerationRepository->save($workoutGeneration);
    }
}
