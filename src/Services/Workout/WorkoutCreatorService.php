<?php

namespace App\Services\Workout;

use App\Entity\Workout\Enum\WorkoutMovementGenerationTypeEnum;
use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use App\Entity\Workout\Enum\WorkoutTypeEnum;
use App\Entity\Workout\Movement;
use App\Entity\Workout\Workout;
use App\Entity\WorkoutGeneration\WorkoutGeneration;

readonly class WorkoutCreatorService implements WorkoutCreatorServiceInterface
{
    public function __construct(
        public MovementServiceInterface $movementService,
        public ChatGPTApiKeyInterface $chatGPTApiKey,
        public WorkoutOriginServiceInterface $workoutOriginService,
        private ?WorkoutPrescriptionStandardPromptBuilder $prescriptionStandardPromptBuilder = null,
    ) {
    }

    public function createWorkout(WorkoutGeneration $workoutGeneration): Workout
    {
        if (count($workoutGeneration->getMandatoryMovements()) > $workoutGeneration->getNumberOfDifferentMovements()) {
            throw new \InvalidArgumentException('The number of mandatory movements cannot be greater than the number of different movements.');
        }
        $this->assertMandatoryMovementsAreNotBanned($workoutGeneration);

        $possibleMovements = [];
        if (count($workoutGeneration->getMandatoryMovements()) < $workoutGeneration->getNumberOfDifferentMovements()) {
            $possibleMovements = match ($workoutGeneration->getMovementGenerationType()->getNameAsEnum()) {
                WorkoutMovementGenerationTypeEnum::MOVEMENT => $this->movementService->getPossibleWorkoutMovementsFromWorkoutGeneration($workoutGeneration),
                WorkoutMovementGenerationTypeEnum::BODY_PART => $this->movementService->getPossibleMovementsFromMuscles($workoutGeneration),
            };
        }

        $mandatoryMovements = $this->movementService->removeNotAvailableImplementsFromMovementsOfWorkout(
            $workoutGeneration->getAvailableImplements(),
            $workoutGeneration->getMandatoryMovements()->toArray()
        );
        $candidateMovements = $this->movementService->removeNotAvailableImplementsFromMovementsOfWorkout(
            $workoutGeneration->getAvailableImplements(),
            $possibleMovements
        );

        if (count($mandatoryMovements) + count($candidateMovements) < $workoutGeneration->getNumberOfDifferentMovements()) {
            throw new \InvalidArgumentException(sprintf('Pas assez de mouvements correspondent aux critères actuels (%d demandé%s, %d disponible%s).', $workoutGeneration->getNumberOfDifferentMovements(), $workoutGeneration->getNumberOfDifferentMovements() > 1 ? 's' : '', count($mandatoryMovements) + count($candidateMovements), count($mandatoryMovements) + count($candidateMovements) > 1 ? 's' : ''));
        }

        $workoutType = $workoutGeneration->getWorkoutType()->getNameAsEnum();
        $imposedNumberOfRounds = $workoutType === WorkoutTypeEnum::AMRAP ? null : $workoutGeneration->getNumberOfRounds();

        // création du prompt ChatGPT :
        $promptForChatGPT = "Create a crossfit workout with the following movements and possible implement for the movement. 
        Consider the complete movement pool below and choose only the movements that best match the requested workout. Choose one implement at most per movement. The execution times are indicative average paces per unit of measure; use them as guidance, not as strict math.
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
        $promptForChatGPT .= $this->teamWorkoutGuidance($workoutGeneration);
        $promptForChatGPT .= "Make the final workout flow match the stimulus identity and intent.\n";
        $promptForChatGPT .= $this->stimulusSpecificGuidance($workoutGeneration);
        $promptForChatGPT .= $this->levelPrescriptionGuidance($workoutGeneration);
        $promptForChatGPT .= $this->prescriptionStandardGuidance($workoutGeneration, array_merge($mandatoryMovements, $candidateMovements));
        $promptForChatGPT .= <<<EOD
When prescribing loaded movements, always include level-appropriate male/female loads in kg when relevant. Use heavier and more technical prescriptions for Elite, standard competitive prescriptions for RX, sustainable prescriptions for Intermediate, and accessible prescriptions for Scaled/Beginner.
Every loaded movement written in the main workout flow must include either kg loads for men/women, a percentage, or a clear loading instruction such as "moderate unbroken load". Do not leave loaded movements without prescription.
Add a short "Scaling options" section at the end of the flow with practical adaptations for RX, Intermediate and Scaled athletes. Preserve the intended stimulus when scaling: change load, range of motion, movement complexity, reps or distance before changing the workout goal.
For high-skill movements, suggest realistic substitutions by level, for example strict HSPU may scale to kipping HSPU, pike HSPU, dumbbell press or hand-release push-ups depending on the level.
The Scaling options section is mandatory. Also return it separately in the JSON "scalingOptions" field.

EOD;
        if ($workoutType === WorkoutTypeEnum::AMRAP) {
            $promptForChatGPT .= "This workout is an AMRAP: create one repeatable movement sequence for the athlete to repeat for as many rounds and reps as possible during the time cap. Do not impose or mention a fixed number of rounds.\n";
        } elseif ($workoutType === WorkoutTypeEnum::FOR_TIME) {
            $promptForChatGPT .= "This workout is a For time, you have to complete the workout as fast as possible.\n";
            if ($imposedNumberOfRounds !== null) {
                $promptForChatGPT .= "The athlete explicitly imposed the number of rounds. Use this structure:\n";
                $promptForChatGPT .= sprintf("%d rounds of : \n", $imposedNumberOfRounds);
            } else {
                $promptForChatGPT .= "The athlete did not impose a number of rounds. Choose the structure that best fits the stimulus, level, time cap and movement pool; it can be rounds, a chipper, a ladder, a couplet or a multi-part workout.\n";
            }
            $promptForChatGPT .= "You may write a natural multi-part workout only if it improves the intended stimulus.\n";
            $promptForChatGPT .= "You don't have to write the number of milliseconds per movement.\n";
        }

        // - Durée de l'entrainement et nombre de tours
        if ($imposedNumberOfRounds !== null) {
            $promptForChatGPT .= sprintf("Choose the number of reps of each movement using the average time per movement as rough guidance, the imposed number of rounds (%s rounds) and the timeCap which is %s minutes.\n", $imposedNumberOfRounds, $workoutGeneration->getTimeCap());
        } else {
            $promptForChatGPT .= sprintf("Choose the movement sequence, reps, distances, intervals and round structure using the average time per movement as rough guidance and the timeCap which is %s minutes. Do not invent a fixed round count unless it is the most coherent structure for the workout format and stimulus.\n", $workoutGeneration->getTimeCap());
        }
        $promptForChatGPT .= sprintf("Choose exactly %d different movement%s for the final workout.\n", $workoutGeneration->getNumberOfDifferentMovements(), $workoutGeneration->getNumberOfDifferentMovements() > 1 ? 's' : '');
        $promptForChatGPT .= "Use only movement names from the mandatory movements and candidate movement pool below.\n";
        $promptForChatGPT .= "Use only the implement options printed under each selected movement. If a movement has no printed implement option because its required implement is unavailable, do not select or prescribe it. Never invent unavailable equipment.\n";
        $promptForChatGPT .= "The workout examples are format references only: do not copy their movement names unless those names are listed below.\n";
        if (count($mandatoryMovements) > 0) {
            $promptForChatGPT .= "Mandatory movements that must appear in the workout:\n";
            $promptForChatGPT .= $this->formatMovementPromptSection($mandatoryMovements);
        }
        $bannedMovements = $workoutGeneration->getBannedMovements()->toArray();
        if (count($bannedMovements) > 0) {
            $promptForChatGPT .= "Banned movements that must not appear in the workout flow:\n";
            $promptForChatGPT .= $this->formatMovementPromptSection($bannedMovements);
        }
        // - Mouvements possibles du workout avec seulement les implements disponibles
        $promptForChatGPT .= "Candidate movement pool. Pick the best movements for the stimulus from this complete pool:\n";
        $promptForChatGPT .= $this->formatMovementPromptSection($candidateMovements);
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
            Split reps anyhow, except burpees synchronized
            -40 Wall balls
            -30 Thrusters
            -20 Burpee box jump overs
            -400m row, split in short 100m switches
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
            -5 Thrusters (40 kg men / 30 kg women)
            -3 rings muscle-ups
            
            -Example 3 :
            For Time (Intervals 2 minutes on / 1 minute off)
            -1500m row
            -52 Thrusters (40 kg men / 30 kg women)
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
            -21 Deadlifts (83 kg men / 61 kg women)
            -15 Pull-Ups
            -9 Front Squats (83 kg men / 61 kg women)
            Time cap : 10 minutes.
            
            -Example 4 :
            For time: 
            5 rounds of:     
                10 thrusters (43 kg men / 29 kg women)
                10 chest-to-bar pull-ups 
            
            Rest 1 minute, then:
            
            5 rounds of: 
                7 thrusters (61 kg men / 43 kg women)
                7 bar muscle-ups 
            
            Time cap : 15 minutes
            EOD;
        }

        // Si c'est un entrainement avec des intervalles, fournir
        // - nb de tours + durée des intervalles + durée des repos
        if ($workoutType === WorkoutTypeEnum::INTERVALS) {
            if ($imposedNumberOfRounds !== null) {
                $promptForChatGPT .= sprintf("The workout pattern is an Intervals workout with %s rounds, each round lasts %s seconds with a rest of %s seconds between each round.\n",
                    $imposedNumberOfRounds,
                    $workoutGeneration->getIntervalsTime() ?? 60,
                    $workoutGeneration->getIntervalsRestTime() ?? 30
                );
            } else {
                $promptForChatGPT .= sprintf("The workout pattern is an Intervals workout. The athlete did not impose the number of rounds; choose the number of intervals that best fits the stimulus and time cap. Each interval should last %s seconds with a rest of %s seconds between intervals unless a better interval structure is clearly justified.\n",
                    $workoutGeneration->getIntervalsTime() ?? 60,
                    $workoutGeneration->getIntervalsRestTime() ?? 30
                );
            }
        }

        // todo : le faire dans une méthode ?
        // Si c'est un choix par partie du corps / muscles, fournir :
        // - Partie(s) du corps ciblée(s) + mouvements possibles pour cette/ces partie(s) du corps

        // Appel à l'API ChatGPT pour générer le nom et le flow de l'entrainement
        $promptForChatGPT .= <<<EOD

Return only valid JSON, with no markdown and no explanation, using this exact shape:
{
  "flow": "The complete workout text displayed to the athlete",
  "scalingOptions": "A short Scaling options section with RX, Intermediate and Scaled adaptations",
  "movements": ["Exact movement name from the allowed lists"]
}
The flow should include the scaling options at the end. The movements array must contain exactly {$workoutGeneration->getNumberOfDifferentMovements()} unique movement name(s), with no duplicates, using only exact names from the allowed lists, and every listed movement must appear in the main flow.
EOD;

        $rawResponse = $this->chatGPTApiKey->getWorkoutFlowFromPrompt($promptForChatGPT);
        $generatedWorkout = $this->parseGeneratedWorkout($rawResponse);
        $this->assertSelectedMovementNamesAppearInFlow(
            $generatedWorkout['movements'],
            array_merge($mandatoryMovements, $candidateMovements),
            $generatedWorkout['flow']
        );
        $allowedMovements = array_merge($mandatoryMovements, $candidateMovements);
        $this->assertMandatoryMovementsAppearInFlow($mandatoryMovements, $allowedMovements, $generatedWorkout['flow']);
        $this->assertBannedMovementsDoNotAppearInFlow($workoutGeneration->getBannedMovements()->toArray(), $allowedMovements, $generatedWorkout['flow']);
        $WorkoutMovements = $this->resolveSelectedMovements(
            $generatedWorkout['movements'],
            $mandatoryMovements,
            $candidateMovements,
            $workoutGeneration->getNumberOfDifferentMovements()
        );
        $this->assertNoUnlistedAllowedMovementsAppearInFlow($WorkoutMovements, $allowedMovements, $generatedWorkout['flow']);
        $flow = $this->flowWithScalingOptions($generatedWorkout['flow'], $generatedWorkout['scalingOptions']);

        // Création de l'entité Workout avec les données reçues
        // todo : faire un service qui crée un workoutOrigin avec l'année courante si il n'existe pas
        $workoutOrigin = $this->workoutOriginService->getExistingOrInsertNewWorkoutOrigin(WorkoutOriginNameEnum::CUSTOM->value, (int) date('Y'));

        return new Workout(
            $workoutGeneration->getName(),
            $flow,
            $imposedNumberOfRounds,
            $workoutGeneration->getTimeCap(),
            $workoutGeneration->getWorkoutType(),
            $workoutOrigin,
            $workoutGeneration->getAvailableImplements()->toArray(),
            $WorkoutMovements,
        )
            ->setWorkoutGeneration($workoutGeneration)
            ->setGenerationPrompt($promptForChatGPT);
    }

    public function createWorkoutVariants(WorkoutGeneration $workoutGeneration): array
    {
        if (count($workoutGeneration->getMandatoryMovements()) > $workoutGeneration->getNumberOfDifferentMovements()) {
            throw new \InvalidArgumentException('The number of mandatory movements cannot be greater than the number of different movements.');
        }
        $this->assertMandatoryMovementsAreNotBanned($workoutGeneration);

        $possibleMovements = [];
        if (count($workoutGeneration->getMandatoryMovements()) < $workoutGeneration->getNumberOfDifferentMovements()) {
            $possibleMovements = match ($workoutGeneration->getMovementGenerationType()->getNameAsEnum()) {
                WorkoutMovementGenerationTypeEnum::MOVEMENT => $this->movementService->getPossibleWorkoutMovementsFromWorkoutGeneration($workoutGeneration),
                WorkoutMovementGenerationTypeEnum::BODY_PART => $this->movementService->getPossibleMovementsFromMuscles($workoutGeneration),
            };
        }

        $mandatoryMovements = $this->movementService->removeNotAvailableImplementsFromMovementsOfWorkout(
            $workoutGeneration->getAvailableImplements(),
            $workoutGeneration->getMandatoryMovements()->toArray()
        );
        $candidateMovements = $this->movementService->removeNotAvailableImplementsFromMovementsOfWorkout(
            $workoutGeneration->getAvailableImplements(),
            $possibleMovements
        );
        $allowedMovements = array_merge($mandatoryMovements, $candidateMovements);

        if (count($allowedMovements) < $workoutGeneration->getNumberOfDifferentMovements()) {
            throw new \InvalidArgumentException(sprintf('Pas assez de mouvements correspondent aux critères actuels (%d demandé%s, %d disponible%s).', $workoutGeneration->getNumberOfDifferentMovements(), $workoutGeneration->getNumberOfDifferentMovements() > 1 ? 's' : '', count($allowedMovements), count($allowedMovements) > 1 ? 's' : ''));
        }

        $promptForChatGPT = "Suggest 3 distinct CrossFit workout concepts before generating a final workout.\n";
        $promptForChatGPT .= sprintf("Workout name: %s\n", $workoutGeneration->getName());
        if ($workoutGeneration->getStimulus() !== null) {
            $promptForChatGPT .= sprintf("Workout stimulus identity: %s\n", $workoutGeneration->getStimulus());
        }
        if ($workoutGeneration->getStimulusIntent() !== null) {
            $promptForChatGPT .= sprintf("Base stimulus intent: %s\n", $workoutGeneration->getStimulusIntent());
        }
        $promptForChatGPT .= sprintf("Athlete level: %s\n", $workoutGeneration->getMovementDifficulty()->getName());
        $promptForChatGPT .= sprintf("Workout format: %s\n", $workoutGeneration->getWorkoutType()->getName());
        $promptForChatGPT .= sprintf("Time cap: %d minutes\n", $workoutGeneration->getTimeCap());
        $promptForChatGPT .= sprintf("Team workout: %s\n", $workoutGeneration->isTeamWorkout() ? 'yes' : 'no');
        $promptForChatGPT .= sprintf("Each concept must use exactly %d movement name(s).\n", $workoutGeneration->getNumberOfDifferentMovements());
        if (count($mandatoryMovements) > 0) {
            $promptForChatGPT .= "Mandatory movements that must appear in every concept:\n";
            $promptForChatGPT .= $this->formatMovementPromptSection($mandatoryMovements);
        }
        $promptForChatGPT .= "Candidate movement pool. Use only exact names from this pool:\n";
        $promptForChatGPT .= $this->formatMovementPromptSection($allowedMovements);
        $promptForChatGPT .= "Use only movements and implement options printed in the pool above. Do not invent unavailable equipment.\n";
        $promptForChatGPT .= $this->stimulusSpecificGuidance($workoutGeneration);
        $promptForChatGPT .= <<<EOD

Return only valid JSON, with no markdown and no explanation, using this exact shape:
{
  "variants": [
    {
      "title": "Short memorable French title",
      "intent": "One precise coaching intent in French",
      "format": "Likely workout structure, for example AMRAP 16, 4 rounds for time, or intervals",
      "movementNames": ["Exact movement name from the allowed lists"],
      "summary": "One short French sentence explaining the feel of the workout"
    }
  ]
}
Every variant must be meaningfully different from the others. Do not write the final workout flow yet.
EOD;

        $payload = $this->decodeOpenAiJsonObject($this->chatGPTApiKey->getWorkoutFlowFromPrompt($promptForChatGPT));
        $variants = $this->variantsFromPayload($payload['variants'] ?? null, $allowedMovements, $workoutGeneration->getNumberOfDifferentMovements());
        if (count($variants) === 0) {
            throw new \RuntimeException('OpenAI workout variant generation returned an invalid variants payload.');
        }

        return $variants;
    }

    /**
     * @param Movement[] $movements
     */
    private function formatMovementPromptSection(array $movements): string
    {
        $prompt = '';
        foreach ($movements as $movement) {
            $prompt .= '- '.$movement->getName()."\n";
            if (count($movement->getPossibleImplements()) === 0) {
                $prompt .= '- no implement required (';
            }
            foreach ($movement->getPossibleImplements() as $implement) {
                $prompt .= '- '.$implement->getName().' (';
                foreach ($movement->getMovementExecutionTimeForMeasureUnits() as $executionTimeForMeasureUnit) {
                    $prompt .= $executionTimeForMeasureUnit->getMeasureUnit()->value.' : '.$executionTimeForMeasureUnit->getExecutionTimeInMilliseconds().' ms,';
                }
                $prompt .= ")\n";
            }
            if (count($movement->getPossibleImplements()) === 0) {
                foreach ($movement->getMovementExecutionTimeForMeasureUnits() as $executionTimeForMeasureUnit) {
                    $prompt .= $executionTimeForMeasureUnit->getMeasureUnit()->value.' : '.$executionTimeForMeasureUnit->getExecutionTimeInMilliseconds().' ms,';
                }
                $prompt .= ")\n";
            }
        }

        return $prompt;
    }

    private function assertMandatoryMovementsAreNotBanned(WorkoutGeneration $workoutGeneration): void
    {
        $bannedMovementNames = [];
        foreach ($workoutGeneration->getBannedMovements() as $movement) {
            $bannedMovementNames[$this->normalizeMovementName($movement->getName())] = true;
        }

        foreach ($workoutGeneration->getMandatoryMovements() as $movement) {
            if (isset($bannedMovementNames[$this->normalizeMovementName($movement->getName())])) {
                throw new \InvalidArgumentException(sprintf('Movement "%s" cannot be both mandatory and banned.', $movement->getName()));
            }
        }
    }

    private function levelPrescriptionGuidance(WorkoutGeneration $workoutGeneration): string
    {
        return match ($workoutGeneration->getMovementDifficulty()->getName()) {
            'Elite' => "Level prescription guidance: create an Elite version with demanding loads, advanced gymnastics where appropriate, and competitive volume. Do not downscale the main workout for accessibility, but still include scaling options below it.\n",
            'RX' => "Level prescription guidance: create an RX version with standard box competition loads and skills. Avoid Elite-only loading unless the stimulus explicitly calls for it.\n",
            'Intermediate' => "Level prescription guidance: create an Intermediate version with moderate loads, manageable skill choices, and repeatable pacing while keeping the intended stimulus.\n",
            'Beginner' => "Level prescription guidance: create a Scaled/Beginner version with accessible loads, simple movement patterns, and lower technical barriers while keeping the intended stimulus.\n",
            default => sprintf("Level prescription guidance: adapt loads, skills and volume to the requested level \"%s\" while keeping the intended stimulus.\n", $workoutGeneration->getMovementDifficulty()->getName()),
        };
    }

    private function stimulusSpecificGuidance(WorkoutGeneration $workoutGeneration): string
    {
        $stimulus = strtolower((string) $workoutGeneration->getStimulus());

        if ($stimulus === '') {
            return '';
        }

        $guidance = "Stimulus-specific guidance:\n";
        if (str_contains($stimulus, 'strength endurance')) {
            $guidance .= "- Strength Endurance: use moderate-to-heavy loads, meaningful volume, and local muscular fatigue. Every loaded movement must have a load or loading instruction. Avoid pure cardio limitation.\n";
        } elseif (str_contains($stimulus, 'strength')) {
            $guidance .= "- Strength: write this like a true strength prescription, for example '5 x 3 Back Squat @ 85-90%, rest 3 min'. Keep reps low, rest long, and avoid turning it into a conditioning interval. Do not write 'Intervals X rounds' for pure strength work; use compact set x rep prescription lines.\n";
        } elseif (str_contains($stimulus, 'sprint')) {
            $guidance .= "- Sprint: keep the workout short, simple, and near-maximal. Target roughly 2-8 minutes, with few transitions and no pacing-heavy volume.\n";
        } elseif (str_contains($stimulus, 'threshold')) {
            $guidance .= "- Threshold: target 8-20 minutes at hard sustainable intensity. Include a pacing note and avoid both all-out sprint volume and slow aerobic cruising.\n";
        } elseif (str_contains($stimulus, 'engine')) {
            $guidance .= "- Engine: make the limitation primarily aerobic. Prefer simple cardio-dominant movements, ergs, running, simple cyclical work, and low technical complexity. Avoid grip-heavy, high-skill gymnastics, and high-rep loaded stations as the main limiter. Do not choose Wall Ball Shot or other equipment-specific movements unless their required implement is explicitly printed in the allowed movement pool.\n";
        } elseif ($this->isFullHyroxStimulus($stimulus)) {
            $guidance .= "- Hyrox complete simulation: write a race-like simulation with 8 ordered functional stations and recurrent run segments between stations. Make the structure explicit: run segment, station 1, run segment, station 2, and so on until station 8. Include distances, calories, reps or loads for each station, and give men/women standards or scaling options when relevant. Keep the flow readable as an ordered simulation instead of compressing everything into generic rounds. The JSON movements list must exactly match the station movements written in the main flow, with no extra movement and no missing movement; recurrent run/erg exposure must also appear in the flow.\n";
        } elseif (str_contains($stimulus, 'hyrox')) {
            $guidance .= "- Hyrox: build a hybrid endurance workout with repeated cardio/run/erg exposure and functional stations. Keep the station count realistic for training, usually 4-6 station movements plus run/erg exposure. Prefer an alternating sequence such as run/erg, station, run/erg, station. If there is only one pass through the sequence, do not write '1 rounds of'; write it as a chipper or ordered sequence. The JSON movements list must exactly match the station movements written in the main flow, with no extra movement and no missing movement.\n";
        } elseif (str_contains($stimulus, 'gymnastics') || str_contains($stimulus, 'skill')) {
            $guidance .= "- Gymnastics / Skill: calibrate complexity and volume to the requested level. For RX, avoid accidental Elite volume; favor quality, control, and repeatable skill practice. Use small sets and clear rest when using muscle-ups, HSPU, handstand walk or toes-to-bar. For RX skill-under-fatigue work, keep total volume manageable instead of testing max capacity. Decide whether it is technical skill work or skill under fatigue and make that explicit.\n";
        } elseif (str_contains($stimulus, 'competition')) {
            $guidance .= "- Competition: combine several qualities and movement families with clear standards and strategic pacing. It can feel Open-like, but must remain coherent for the requested level.\n";
        }

        $guidance .= "- JSON integrity: the movements array must contain exactly the movement names used in the main workout flow, no extra movement and no missing movement.\n";

        return $guidance;
    }

    private function isFullHyroxStimulus(string $stimulus): bool
    {
        return str_contains($stimulus, 'simulation hyrox')
            || str_contains($stimulus, 'hyrox complet')
            || str_contains($stimulus, 'full hyrox')
            || str_contains($stimulus, 'complete hyrox');
    }

    private function teamWorkoutGuidance(WorkoutGeneration $workoutGeneration): string
    {
        if (!$workoutGeneration->isTeamWorkout()) {
            return "Team workout guidance: this is an individual workout. Do not use partner relay, shared reps or synchronized work.\n";
        }

        return <<<TXT
Team workout guidance: this must be explicitly written as a team workout. Use team-of-2 unless another team size is clearly better for the stimulus. Include a clear work-sharing pattern such as short "you go, I go" switches, shared reps, split anyhow, synchronized reps, relay stations or partner alternating rounds. The flow must make the team structure impossible to miss.
Avoid long idle partner windows: do not prescribe "you go, I go" chunks where one athlete waits 2-3 minutes while the other completes a long station, long row/run, large unbroken set or full round. If using "you go, I go", keep each switch short enough that the resting partner is back to work quickly, usually small sets, short distances or one compact movement at a time.
For longer stations, prefer shared reps, split-anyhow work, synchronized work, active holds/carries for the waiting partner, or explicit short distance/repetition switches.

TXT;
    }

    /**
     * @param Movement[] $movements
     */
    private function prescriptionStandardGuidance(WorkoutGeneration $workoutGeneration, array $movements): string
    {
        if (!$this->prescriptionStandardPromptBuilder instanceof WorkoutPrescriptionStandardPromptBuilder) {
            return '';
        }

        return $this->prescriptionStandardPromptBuilder->build($workoutGeneration, $movements);
    }

    /**
     * @return array{flow: string, scalingOptions: string, movements: list<string>}
     */
    private function parseGeneratedWorkout(string $rawResponse): array
    {
        $payload = $this->decodeOpenAiJsonObject($rawResponse);

        $flow = trim((string) ($payload['flow'] ?? ''));
        $scalingOptions = $this->scalingOptionsFromPayload($payload['scalingOptions'] ?? '');
        if ($scalingOptions === '') {
            $scalingOptions = $this->scalingOptionsFromFlow($flow);
        }
        $movements = $this->movementNamesFromPayload($payload['movements'] ?? []);
        if ($flow === '' || $scalingOptions === '' || !is_array($movements)) {
            throw new \RuntimeException('OpenAI workout generation returned an invalid workout payload.');
        }

        return [
            'flow' => $flow,
            'scalingOptions' => $scalingOptions,
            'movements' => $movements,
        ];
    }

    private function decodeOpenAiJsonObject(string $rawResponse): array
    {
        $json = trim($rawResponse);
        if (str_starts_with($json, '```')) {
            $json = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $json) ?? $json;
        }
        if (!str_starts_with($json, '{')) {
            $start = strpos($json, '{');
            $end = strrpos($json, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $json = substr($json, $start, $end - $start + 1);
            }
        }

        try {
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('OpenAI workout generation returned invalid JSON.', 0, $exception);
        }

        if (!is_array($payload)) {
            throw new \RuntimeException('OpenAI workout generation returned an invalid JSON payload.');
        }

        return $payload;
    }

    /**
     * @param Movement[] $allowedMovements
     *
     * @return list<array{title: string, intent: string, format: string, movementNames: list<string>, summary: string}>
     */
    private function variantsFromPayload(mixed $variants, array $allowedMovements, int $expectedMovementCount): array
    {
        if (!is_array($variants)) {
            return [];
        }

        $allowedMovementsByName = $this->movementsBySearchText($allowedMovements);
        $normalizedAllowedMovementNames = [];
        foreach ($allowedMovements as $movement) {
            $normalizedAllowedMovementNames[$this->normalizeMovementSearchText($movement->getName())] = $movement->getName();
        }

        $parsedVariants = [];
        foreach ($variants as $variant) {
            if (!is_array($variant)) {
                continue;
            }

            $title = trim((string) ($variant['title'] ?? ''));
            $intent = trim((string) ($variant['intent'] ?? ''));
            $format = trim((string) ($variant['format'] ?? ''));
            $summary = trim((string) ($variant['summary'] ?? ''));
            $movementNames = $this->movementNamesFromPayload($variant['movementNames'] ?? $variant['movements'] ?? []);
            if ($title === '' || $intent === '' || $format === '' || $summary === '' || !is_array($movementNames)) {
                continue;
            }

            $resolvedMovementNames = [];
            foreach ($movementNames as $movementName) {
                $normalizedMovementName = $this->normalizeMovementSearchText($movementName);
                if (!isset($allowedMovementsByName[$normalizedMovementName], $normalizedAllowedMovementNames[$normalizedMovementName])) {
                    continue 2;
                }
                $resolvedMovementNames[$normalizedMovementName] = $normalizedAllowedMovementNames[$normalizedMovementName];
            }

            if (count($resolvedMovementNames) !== $expectedMovementCount) {
                continue;
            }

            $parsedVariants[] = [
                'title' => $title,
                'intent' => $intent,
                'format' => $format,
                'movementNames' => array_values($resolvedMovementNames),
                'summary' => $summary,
            ];
        }

        return $parsedVariants;
    }

    /**
     * @return list<string>|null
     */
    private function movementNamesFromPayload(mixed $movements): ?array
    {
        if (is_string($movements)) {
            $movements = preg_split('/[,;\n]+/', $movements) ?: [];
        }

        if (!is_array($movements)) {
            return null;
        }

        $movementNames = [];
        foreach ($movements as $movement) {
            $movementName = self::movementNameFromPayloadItem($movement);
            if ($movementName === null) {
                return null;
            }

            $movementNames[] = $movementName;
        }

        return $movementNames;
    }

    private static function movementNameFromPayloadItem(mixed $movement): ?string
    {
        if (is_array($movement) && array_key_exists('name', $movement)) {
            $movement = $movement['name'];
        }

        return is_string($movement) && trim($movement) !== '' ? trim($movement) : null;
    }

    private function scalingOptionsFromPayload(mixed $scalingOptions): string
    {
        if (is_string($scalingOptions)) {
            return trim($scalingOptions);
        }

        if (!is_array($scalingOptions)) {
            return '';
        }

        $lines = [];
        foreach ($scalingOptions as $key => $option) {
            $line = $this->scalingOptionLineFromPayloadItem($option, is_string($key) ? $key : null);
            if ($line !== null) {
                $lines[] = $line;
            }
        }

        return trim(implode("\n", $lines));
    }

    private function scalingOptionLineFromPayloadItem(mixed $option, ?string $fallbackLevel): ?string
    {
        if (is_string($option)) {
            $option = trim($option);

            return $option !== '' ? $option : null;
        }

        if (!is_array($option)) {
            return null;
        }

        $level = $option['level'] ?? $option['name'] ?? $option['title'] ?? $fallbackLevel;
        $text = $option['description'] ?? $option['option'] ?? $option['adaptation'] ?? $option['text'] ?? null;

        if (!is_string($text) || trim($text) === '') {
            return null;
        }

        if (is_string($level) && trim($level) !== '') {
            return trim($level).': '.trim($text);
        }

        return trim($text);
    }

    private function scalingOptionsFromFlow(string $flow): string
    {
        if (preg_match('/^\s*scaling(?: options)?\s*:\s*(?<scaling>.*?)(?:\n{2,}\S[^\n]*:|\z)/mis', $flow, $matches) !== 1) {
            return '';
        }

        return trim((string) $matches['scaling']);
    }

    private function flowWithScalingOptions(string $flow, string $scalingOptions): string
    {
        if (preg_match('/^\s*scaling(?: options)?\s*:/mi', $flow) === 1) {
            return $flow;
        }

        return rtrim($flow)."\n\nScaling options:\n".trim($scalingOptions);
    }

    /**
     * @param list<string> $selectedMovementNames
     * @param Movement[]   $allowedMovements
     */
    private function assertSelectedMovementNamesAppearInFlow(array $selectedMovementNames, array $allowedMovements, string $flow): void
    {
        $allowedMovementsByName = $this->movementsBySearchText($allowedMovements);

        $mainFlow = $this->flowWithoutScalingOptions($flow);

        foreach ($selectedMovementNames as $selectedMovementName) {
            $movement = $allowedMovementsByName[$this->normalizeMovementSearchText($selectedMovementName)] ?? null;
            if (!$movement instanceof Movement) {
                continue;
            }

            $normalizedFlow = $this->normalizedFlowWithoutOtherMovementNames($mainFlow, $allowedMovements, $movement);
            if (!$this->normalizedFlowContainsMovement($normalizedFlow, $movement)) {
                throw new \RuntimeException(sprintf('OpenAI workout generation listed movement "%s" but did not include it in the workout flow.', $movement->getName()));
            }
        }
    }

    /**
     * @param Movement[] $selectedMovements
     * @param Movement[] $allowedMovements
     */
    private function assertNoUnlistedAllowedMovementsAppearInFlow(array $selectedMovements, array $allowedMovements, string $flow): void
    {
        $selectedMovementNames = [];
        foreach ($selectedMovements as $movement) {
            $selectedMovementNames[$this->normalizeMovementName($movement->getName())] = true;
        }

        $mainFlow = $this->flowWithoutScalingOptions($flow);
        $normalizedFlow = $this->normalizeMovementSearchText($mainFlow);
        $normalizedSelectedMovementSearchTexts = $this->movementSearchTexts($selectedMovements);
        usort($normalizedSelectedMovementSearchTexts, static fn (string $left, string $right): int => strlen($right) <=> strlen($left));
        $flowWithoutSelectedMovements = str_replace($normalizedSelectedMovementSearchTexts, '', $normalizedFlow);

        foreach ($allowedMovements as $movement) {
            if (isset($selectedMovementNames[$this->normalizeMovementName($movement->getName())])) {
                continue;
            }

            if ($this->normalizedFlowContainsMovement($flowWithoutSelectedMovements, $movement)) {
                throw new \RuntimeException(sprintf('OpenAI workout generation included movement "%s" in the flow but did not list it in movements.', $movement->getName()));
            }
        }
    }

    /**
     * @param Movement[] $mandatoryMovements
     * @param Movement[] $allowedMovements
     */
    private function assertMandatoryMovementsAppearInFlow(array $mandatoryMovements, array $allowedMovements, string $flow): void
    {
        $mainFlow = $this->flowWithoutScalingOptions($flow);

        foreach ($mandatoryMovements as $movement) {
            $normalizedFlow = $this->normalizedFlowWithoutOtherMovementNames($mainFlow, $allowedMovements, $movement);
            if (!$this->normalizedFlowContainsMovement($normalizedFlow, $movement)) {
                throw new \RuntimeException(sprintf('OpenAI workout generation did not include mandatory movement "%s" in the workout flow.', $movement->getName()));
            }
        }
    }

    /**
     * @param Movement[] $bannedMovements
     * @param Movement[] $allowedMovements
     */
    private function assertBannedMovementsDoNotAppearInFlow(array $bannedMovements, array $allowedMovements, string $flow): void
    {
        $bannedMovementNames = [];
        foreach ($bannedMovements as $movement) {
            $bannedMovementNames[$this->normalizeMovementName($movement->getName())] = true;
        }

        $allowedNonBannedMovements = array_values(array_filter(
            $allowedMovements,
            fn (Movement $movement): bool => !isset($bannedMovementNames[$this->normalizeMovementName($movement->getName())])
        ));
        $mainFlow = $this->flowWithoutScalingOptions($flow);
        $normalizedFlow = $this->normalizedFlowWithMovementsRemoved($mainFlow, $allowedNonBannedMovements);

        foreach ($bannedMovements as $movement) {
            if ($this->normalizedFlowContainsMovement($normalizedFlow, $movement)) {
                throw new \RuntimeException(sprintf('OpenAI workout generation included banned movement "%s" in the workout flow.', $movement->getName()));
            }
        }
    }

    private function flowWithoutScalingOptions(string $flow): string
    {
        $parts = preg_split('/^\s*scaling(?: options)?\s*:/mi', $flow, 2);

        return is_array($parts) ? $parts[0] : $flow;
    }

    private function normalizeMovementSearchText(string $text): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower($text)) ?? '');
    }

    private function normalizedFlowContainsMovement(string $normalizedFlow, Movement $movement): bool
    {
        foreach ($this->movementSearchTexts([$movement]) as $movementSearchText) {
            if (preg_match('/(?:^| )'.preg_quote($movementSearchText, '/').'s?(?: |$)/', $normalizedFlow) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Movement[] $movements
     *
     * @return list<string>
     */
    private function movementSearchTexts(array $movements): array
    {
        $searchTexts = [];
        foreach ($movements as $movement) {
            $movementName = $movement->getName();
            array_push($searchTexts, ...$this->movementSearchTextVariants($this->normalizeMovementSearchText($movementName)));

            foreach ($this->movementSearchAliases($movementName) as $alias) {
                array_push($searchTexts, ...$this->movementSearchTextVariants($this->normalizeMovementSearchText($alias)));
            }
        }

        return array_values(array_unique(array_filter($searchTexts)));
    }

    /**
     * @return list<string>
     */
    private function movementSearchTextVariants(string $searchText): array
    {
        $variants = [$searchText];

        if (str_ends_with($searchText, 'y')) {
            $variants[] = substr($searchText, 0, -1).'ies';
        }

        if (!str_ends_with($searchText, 's')) {
            $variants[] = $searchText.'s';
        }

        return $variants;
    }

    /**
     * @return list<string>
     */
    private function movementSearchAliases(string $movementName): array
    {
        return match ($this->normalizeMovementName($movementName)) {
            'bike erg' => ['BikeErg', 'Bike Erg Calories', 'Bike Erg Calorie'],
            'box jump over' => ['Box Jump-Over', 'Box Jump-Overs', 'Box Jump Overs'],
            'box step up' => ['Box Step-Up', 'Box Step-Ups', 'Box Step Ups'],
            'burpee box jump over' => ['BBJO', 'BBJOs', 'Burpee Box Jump-Over', 'Burpee Box Jump-Overs', 'Burpee Box Jump Overs'],
            'chest to bar pull up' => ['C2B', 'C2B Pull Up', 'C2B Pull Ups', 'Chest to Bar Pull Ups', 'Chest to Bars'],
            'clean and jerk' => ['C&J', 'Clean & Jerk', 'Clean + Jerk', 'Clean Jerks'],
            'double under' => ['DU', 'DUs', 'Double Unders'],
            'farmer carry' => ['Farmer Carrys', 'Farmer Carries', 'Farmer\'s Carry', 'Farmer\'s Carries', 'Farmers Carry', 'Farmers Carries'],
            'ghd sit up' => ['GHD Sit-Up', 'GHD Sit-Ups', 'GHD Sit Ups'],
            'handstand walk' => ['HS Walk', 'HS Walks', 'HSW', 'HSWs', 'Handstand Walks'],
            'handstand push up' => ['HSPU', 'HSPUs', 'Handstand Push Ups'],
            'muscle up' => ['BMU', 'BMUs', 'Bar Muscle Up', 'Bar Muscle Ups', 'RMU', 'RMUs', 'Ring Muscle Up', 'Ring Muscle Ups'],
            'overhead squat' => ['OHS', 'Overhead Squats'],
            'pistol squat' => ['Pistol', 'Pistols', 'Pistol Squats'],
            'row' => ['Rowing', 'Rower', 'Calorie Row', 'Calorie Rows', 'Row Calories', 'Rowing for Calories'],
            'shoulder to overhead' => ['S2OH', 'STOH', 'Shoulder-to-Overhead', 'Shoulder-to-Overheads', 'Shoulder Overhead'],
            'sled drag' => ['Sled Drags', 'Sled-Drag', 'Sled-Drags'],
            'sled pull' => ['Sled Pulls', 'Sled-Pull', 'Sled-Pulls'],
            'sled push' => ['Sled Pushes', 'Sled-Push', 'Sled-Pushes'],
            'ski erg' => ['Ski', 'SkiErg', 'Ski Erg Calories', 'Ski Calories', 'Skiing'],
            'strict handstand push up' => ['Strict HSPU', 'Strict HSPUs', 'SHSPU', 'SHSPUs', 'Strict Handstand Push Ups'],
            'toes to bar' => ['T2B', 'TTB', 'Toes to Bars'],
            'wall ball shot' => ['Wall Ball', 'Wall Balls', 'Wall Ball Shots'],
            'wall walk' => ['Wall Walks'],
            default => [],
        };
    }

    /**
     * @param Movement[] $movements
     *
     * @return array<string, Movement>
     */
    private function movementsBySearchText(array $movements): array
    {
        $movementsBySearchText = [];
        $ambiguousSearchTexts = [];

        foreach ($movements as $movement) {
            foreach ($this->movementSearchTexts([$movement]) as $movementSearchText) {
                if (isset($ambiguousSearchTexts[$movementSearchText])) {
                    continue;
                }

                $existingMovement = $movementsBySearchText[$movementSearchText] ?? null;
                if ($existingMovement instanceof Movement && $existingMovement !== $movement) {
                    unset($movementsBySearchText[$movementSearchText]);
                    $ambiguousSearchTexts[$movementSearchText] = true;
                    continue;
                }

                $movementsBySearchText[$movementSearchText] = $movement;
            }
        }

        return $movementsBySearchText;
    }

    /**
     * @param Movement[] $movements
     */
    private function normalizedFlowWithoutOtherMovementNames(string $flow, array $movements, Movement $movementToKeep): string
    {
        $movementToKeepName = $this->normalizeMovementName($movementToKeep->getName());
        $movementsToRemove = array_values(array_filter(
            $movements,
            fn (Movement $movement): bool => $this->normalizeMovementName($movement->getName()) !== $movementToKeepName
        ));
        $movementToKeepSearchTexts = $this->movementSearchTexts([$movementToKeep]);

        return $this->normalizedFlowWithMovementsRemoved($flow, $movementsToRemove, $movementToKeepSearchTexts);
    }

    /**
     * @param Movement[]   $movements
     * @param list<string> $protectedSearchTexts
     */
    private function normalizedFlowWithMovementsRemoved(string $flow, array $movements, array $protectedSearchTexts = []): string
    {
        $normalizedFlow = $this->normalizeMovementSearchText($flow);
        $movementSearchTexts = array_filter(
            $this->movementSearchTexts($movements),
            static fn (string $movementSearchText): bool => !array_any(
                $protectedSearchTexts,
                static fn (string $protectedSearchText): bool => $movementSearchText !== $protectedSearchText
                    && str_contains($protectedSearchText, $movementSearchText)
            )
        );
        usort($movementSearchTexts, static fn (string $left, string $right): int => strlen($right) <=> strlen($left));

        return str_replace($movementSearchTexts, '', $normalizedFlow);
    }

    /**
     * @param list<string> $selectedMovementNames
     * @param Movement[]   $mandatoryMovements
     * @param Movement[]   $candidateMovements
     *
     * @return Movement[]
     */
    private function resolveSelectedMovements(array $selectedMovementNames, array $mandatoryMovements, array $candidateMovements, int $targetCount): array
    {
        $allowedMovementsByName = $this->movementsBySearchText(array_merge($mandatoryMovements, $candidateMovements));

        $selectedMovementsByName = [];
        foreach ($mandatoryMovements as $movement) {
            $selectedMovementsByName[$this->normalizeMovementName($movement->getName())] = $movement;
        }

        $matchedSelectedMovementCount = 0;
        $seenSelectedMovementNames = [];
        $seenSelectedCanonicalMovementNames = [];
        foreach ($selectedMovementNames as $selectedMovementName) {
            $normalizedSelectedMovementName = $this->normalizeMovementSearchText($selectedMovementName);
            if (isset($seenSelectedMovementNames[$normalizedSelectedMovementName])) {
                throw new \RuntimeException(sprintf('OpenAI workout generation returned duplicate movement "%s".', $selectedMovementName));
            }
            $seenSelectedMovementNames[$normalizedSelectedMovementName] = true;

            $movement = $allowedMovementsByName[$normalizedSelectedMovementName] ?? null;
            if (!$movement instanceof Movement) {
                throw new \RuntimeException(sprintf('OpenAI workout generation returned unrecognized movement "%s".', $selectedMovementName));
            }
            $canonicalMovementName = $this->normalizeMovementName($movement->getName());
            if (isset($seenSelectedCanonicalMovementNames[$canonicalMovementName])) {
                throw new \RuntimeException(sprintf('OpenAI workout generation returned duplicate movement "%s".', $selectedMovementName));
            }
            $seenSelectedCanonicalMovementNames[$canonicalMovementName] = true;

            ++$matchedSelectedMovementCount;
            $selectedMovementsByName[$canonicalMovementName] = $movement;
        }

        if ($matchedSelectedMovementCount === 0) {
            throw new \RuntimeException('OpenAI workout generation did not return any allowed movement names.');
        }

        if (count($selectedMovementsByName) < $targetCount) {
            throw new \RuntimeException(sprintf('OpenAI workout generation returned %d allowed movement%s, expected %d.', count($selectedMovementsByName), count($selectedMovementsByName) > 1 ? 's' : '', $targetCount));
        }

        if (count($selectedMovementsByName) > $targetCount) {
            throw new \RuntimeException(sprintf('OpenAI workout generation returned %d allowed movement%s, expected %d.', count($selectedMovementsByName), count($selectedMovementsByName) > 1 ? 's' : '', $targetCount));
        }

        return array_slice(array_values($selectedMovementsByName), 0, $targetCount);
    }

    private function normalizeMovementName(string $name): string
    {
        return strtolower(trim($name));
    }
}
