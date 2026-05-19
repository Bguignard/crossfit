<?php

namespace App\Services\Workout;

use App\Entity\Workout\Enum\WorkoutMovementGenerationTypeEnum;
use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use App\Entity\Workout\Enum\WorkoutTypeEnum;
use App\Entity\Workout\Workout;
use App\Entity\WorkoutGeneration\WorkoutGeneration;

readonly class WorkoutCreatorService implements WorkoutCreatorServiceInterface
{
    public function __construct(
        public MovementServiceInterface $movementService,
        public ChatGPTApiKeyInterface $chatGPTApiKey,
        public WorkoutOriginServiceInterface $workoutOriginService,
    ) {
    }

    public function createWorkout(WorkoutGeneration $workoutGeneration): Workout
    {
        if (count($workoutGeneration->getMandatoryMovements()) > $workoutGeneration->getNumberOfDifferentMovements()) {
            throw new \InvalidArgumentException('The number of mandatory movements cannot be greater than the number of different movements.');
        } elseif (count($workoutGeneration->getMandatoryMovements()) === $workoutGeneration->getNumberOfDifferentMovements()) {
            $WorkoutMovements =
                $this->movementService->removeNotAvailableImplementsFromMovementsOfWorkout(
                    $workoutGeneration->getAvailableImplements(), $workoutGeneration->getMandatoryMovements()->toArray()
                );
        } else {
            if ($workoutGeneration->getMovementGenerationType()->getNameAsEnum() === WorkoutMovementGenerationTypeEnum::MOVEMENT) {
                $WorkoutMovements = $this->movementService->removeNotAvailableImplementsFromMovementsOfWorkout(
                    $workoutGeneration->getAvailableImplements(),
                    $this->movementService->getWorkoutMovementsFromWorkoutGeneration($workoutGeneration)
                );
            } elseif ($workoutGeneration->getMovementGenerationType()->getNameAsEnum() === WorkoutMovementGenerationTypeEnum::BODY_PART) {
                $WorkoutMovements =
                    $this->movementService->removeNotAvailableImplementsFromMovementsOfWorkout(
                        $workoutGeneration->getAvailableImplements(),
                        $this->movementService->getMovementsFromMuscles($workoutGeneration)
                    );
            }
        }

        // if no number of runds, we set a default value
        $numberOfRounds = $workoutGeneration->getNumberOfRounds() ?? rand(1, 10);
        if ($workoutGeneration->getWorkoutType()->getNameAsEnum() === WorkoutTypeEnum::AMRAP) {
            $numberOfRounds = 1;
        }

        // création du prompt ChatGPT :
        $promptForChatGPT = "Create a crossfit workout with the following movements and possible implement for the movement. 
        Take all following movements but just chose one implement per movement. The execution times are indicative average paces per unit of measure; use them as guidance, not as strict math.
        Movement you have to use and implements are given following this pattern :\n";
        $promptForChatGPT .=
            "-Name of movement
        \n possible implement1 (measure unit 1 : time of execution in milliseconds,measure unit 2 : time of execution in milliseconds,...,), 
        \n possible implement2 (measure unit 1 : time of execution in milliseconds,measure unit 2 : time of execution in milliseconds,...,), 
        \n ...)\n";
        $promptForChatGPT .= sprintf("Workout name: %s\n", $workoutGeneration->getName());
        if ($workoutGeneration->getStimulus() !== null) {
            $promptForChatGPT .= sprintf("Workout stimulus identity: %s\n", $workoutGeneration->getStimulus());
        }
        if ($workoutGeneration->getStimulusIntent() !== null) {
            $promptForChatGPT .= sprintf("Stimulus intent: %s\n", $workoutGeneration->getStimulusIntent());
        }
        $promptForChatGPT .= sprintf("Athlete level: %s\n", $workoutGeneration->getMovementDifficulty()->getName());
        $promptForChatGPT .= sprintf("Team workout: %s\n", $workoutGeneration->isTeamWorkout() ? 'yes' : 'no');
        $promptForChatGPT .= "Make the final workout flow match the stimulus identity and intent.\n";
        if ($workoutGeneration->getWorkoutType()->getNameAsEnum() === WorkoutTypeEnum::AMRAP) {
            $promptForChatGPT .= "This workout is an AMRAP, there is only one round to repeat as many rounds as possible in the time cap.\n";
        } elseif ($workoutGeneration->getWorkoutType()->getNameAsEnum() === WorkoutTypeEnum::FOR_TIME) {
            $promptForChatGPT .= "This workout is a For time, you have to complete the workout as fast as possible.\n";
            $promptForChatGPT .= "Even if there are many rounds, you don't have to write them all, just write :\n";
            $promptForChatGPT .= sprintf("%d rounds of : \n", $numberOfRounds);
            $promptForChatGPT .= "You may write a natural multi-part workout only if it improves the intended stimulus.\n";
            $promptForChatGPT .= "You don't have to write the number of milliseconds per movement.\n";
        }

        // - Durée de l'entrainement et nombre de tours
        $promptForChatGPT .= sprintf("Choose the number of reps of each movement using the average time per movement as rough guidance, the number of rounds (there are %s rounds) and the timeCap which is %s minutes.\n", $numberOfRounds, $workoutGeneration->getTimeCap());
        // - Mouvements du workout avec seulement les implements disponibles
        $promptForChatGPT .= "Movements and possible implements :\n";
        foreach ($WorkoutMovements as $movement) {
            $promptForChatGPT .= '-'.$movement->getName()."\n";
            foreach ($movement->getPossibleImplements() as $implement) {
                $promptForChatGPT .= '- '.$implement->getName().' (';
                foreach ($movement->getMovementExecutionTimeForMeasureUnits() as $executionTimeForMeasureUnit) {
                    $promptForChatGPT .= $executionTimeForMeasureUnit->getMeasureUnit()->value.' : '.$executionTimeForMeasureUnit->getExecutionTimeInMilliseconds().' ms,';
                }
                $promptForChatGPT .= ")\n";
            }
        }
        // - Type d'entrainement (AMRAP, EMOM/Intervals, For time, etc.)
        $promptForChatGPT .= 'The workout must be in the following format :'.$workoutGeneration->getWorkoutType()->getName()."\n";

        // EXAMPLES
        $promptForChatGPT .= "Here are some examples of workouts in the same format you have to use :\n";
        if ($workoutGeneration->getWorkoutType()->getNameAsEnum() === WorkoutTypeEnum::AMRAP) {
            $promptForChatGPT .= <<<EOD
            -Example 1 :
            AMRAP 20 minutes
            -5 Pull-ups
            -10 Push-ups
            -15 Air squats
            
            -Example 2 :
            Team Workout (team of 2) :
            AMRAP 20 minutes
            You go I go
            -250m row
            -5 Thrusters
            -10 Wall ball
            -5 Burpees box jump over
            EOD;
        } elseif ($workoutGeneration->getWorkoutType()->getNameAsEnum() === WorkoutTypeEnum::INTERVALS) {
            $promptForChatGPT .= <<<EOD
            -Example 1 :
            EMOM 20 minutes
            -5 Pull-ups
            -10 Push-ups
            -15 Air squats
            
            -Example 2 :
            Intervals 2 minutes on / 1 minute off for 20 minutes
            -250m row
            -5 Thrusters (40/30 kgs)
            -3 rings muscle-ups
            
            -Example 3 :
            For Time (Intervals 2 minutes on / 1 minute off)
            -1500m row
            -52 Thrusters (40/30 kgs)
            -30 rings muscle-ups
            Time cap : 20 minutes.
            EOD;
        } elseif ($workoutGeneration->getWorkoutType()->getNameAsEnum() === WorkoutTypeEnum::FOR_TIME) {
            $promptForChatGPT .= <<<EOD
            -Example 1 :
            For time:
            50-40-30-20-10
            -Double-Unders
            -Sit-Ups
            Time cap : 20 minutes.
            
            -Example 2 :
            5 rounds for time:
            -25 Pull-Ups
            -50 Push-Ups
            -75 Squats
            -100 m Sprint
            Time cap : 25 minutes.
            
            -Example 3 :
            3 rounds for time:
            -21 Deadlifts (185/135 lb)
            -15 Pull-Ups
            -9 Front Squats (185/135 lb)
            Time cap : 10 minutes.
            
            -Example 4 :
            For time: 
            5 rounds of:     
                10 thrusters (29 / 43 kgs)
                10 chest-to-bar pull-ups 
            
            Rest 1 minute, then:
            
            5 rounds of: 
                7 thrusters (43 / 61 kgs)
                7 bar muscle-ups 
            
            Time cap : 15 minutes
            EOD;
        }

        // Si c'est un entrainement avec des intervalles, fournir
        // - nb de tours + durée des intervalles + durée des repos
        if ($workoutGeneration->getWorkoutType()->getNameAsEnum() === WorkoutTypeEnum::INTERVALS) {
            $promptForChatGPT .= sprintf("The workout pattern is an Intervals workout with %s rounds, each round last %s seconds with a rest of %s seconds between each round.\n",
                $numberOfRounds,
                $workoutGeneration->getIntervalsTime() ?? 60,
                $workoutGeneration->getIntervalsRestTime() ?? 30
            );
        }

        // todo : le faire dans une méthode ?
        // Si c'est un choix par partie du corps / muscles, fournir :
        // - Partie(s) du corps ciblée(s) + mouvements possibles pour cette/ces partie(s) du corps

        // Appel à l'API ChatGPT pour générer le nom et le flow de l'entrainement
        $flow = $this->chatGPTApiKey->getWorkoutFlowFromPrompt($promptForChatGPT);

        // Création de l'entité Workout avec les données reçues
        // todo : faire un service qui crée un workoutOrigin avec l'année courante si il n'existe pas
        $workoutOrigin = $this->workoutOriginService->getExistingOrInsertNewWorkoutOrigin(WorkoutOriginNameEnum::CUSTOM->value, (int) date('Y'));

        return new Workout(
            $workoutGeneration->getName(),
            $flow,
            $workoutGeneration->getNumberOfRounds(),
            $workoutGeneration->getTimeCap(),
            $workoutGeneration->getWorkoutType(),
            $workoutOrigin,
            $workoutGeneration->getAvailableImplements()->toArray(),
            $WorkoutMovements,
        )
            ->setWorkoutGeneration($workoutGeneration)
            ->setGenerationPrompt($promptForChatGPT);
    }
}
