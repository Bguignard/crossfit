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

        // if no number of runds, we set a default value
        $numberOfRounds = $workoutGeneration->getNumberOfRounds() ?? rand(1, 10);
        if ($workoutGeneration->getWorkoutType()->getNameAsEnum() === WorkoutTypeEnum::AMRAP) {
            $numberOfRounds = 1;
        }

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
        $promptForChatGPT .= $this->levelPrescriptionGuidance($workoutGeneration);
        $promptForChatGPT .= $this->prescriptionStandardGuidance($workoutGeneration, array_merge($mandatoryMovements, $candidateMovements));
        $promptForChatGPT .= <<<EOD
When prescribing loaded movements, always include level-appropriate male/female loads in kg when relevant. Use heavier and more technical prescriptions for Elite, standard competitive prescriptions for RX, sustainable prescriptions for Intermediate, and accessible prescriptions for Scaled/Beginner.
Add a short "Scaling options" section at the end of the flow with practical adaptations for RX, Intermediate and Scaled athletes. Preserve the intended stimulus when scaling: change load, range of motion, movement complexity, reps or distance before changing the workout goal.
For high-skill movements, suggest realistic substitutions by level, for example strict HSPU may scale to kipping HSPU, pike HSPU, dumbbell press or hand-release push-ups depending on the level.
The Scaling options section is mandatory. Also return it separately in the JSON "scalingOptions" field.

EOD;
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
        $promptForChatGPT .= sprintf("Choose exactly %d different movement%s for the final workout.\n", $workoutGeneration->getNumberOfDifferentMovements(), $workoutGeneration->getNumberOfDifferentMovements() > 1 ? 's' : '');
        $promptForChatGPT .= "Use only movement names from the mandatory movements and candidate movement pool below.\n";
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
        $promptForChatGPT .= <<<EOD

Return only valid JSON, with no markdown and no explanation, using this exact shape:
{
  "flow": "The complete workout text displayed to the athlete",
  "scalingOptions": "A short Scaling options section with RX, Intermediate and Scaled adaptations",
  "movements": ["Exact movement name from the allowed lists"]
}
The flow should include the scaling options at the end. The movements array must contain the exact selected movement names used in the flow.
EOD;

        $rawResponse = $this->chatGPTApiKey->getWorkoutFlowFromPrompt($promptForChatGPT);
        $generatedWorkout = $this->parseGeneratedWorkout($rawResponse);
        $this->assertSelectedMovementNamesAppearInFlow(
            $generatedWorkout['movements'],
            array_merge($mandatoryMovements, $candidateMovements),
            $generatedWorkout['flow']
        );
        $this->assertMandatoryMovementsAppearInFlow($mandatoryMovements, $generatedWorkout['flow']);
        $this->assertBannedMovementsDoNotAppearInFlow($workoutGeneration->getBannedMovements()->toArray(), $generatedWorkout['flow']);
        $flow = $this->flowWithScalingOptions($generatedWorkout['flow'], $generatedWorkout['scalingOptions']);
        $WorkoutMovements = $this->resolveSelectedMovements(
            $generatedWorkout['movements'],
            $mandatoryMovements,
            $candidateMovements,
            $workoutGeneration->getNumberOfDifferentMovements()
        );

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

    private function teamWorkoutGuidance(WorkoutGeneration $workoutGeneration): string
    {
        if (!$workoutGeneration->isTeamWorkout()) {
            return "Team workout guidance: this is an individual workout. Do not use partner relay, shared reps or synchronized work.\n";
        }

        return <<<TXT
Team workout guidance: this must be explicitly written as a team workout. Use team-of-2 unless another team size is clearly better for the stimulus. Include a clear work-sharing pattern such as "you go, I go", shared reps, split anyhow, synchronized reps, relay stations or partner alternating rounds. The flow must make the team structure impossible to miss.

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
        $allowedMovementsByName = [];
        foreach ($allowedMovements as $movement) {
            $allowedMovementsByName[$this->normalizeMovementName($movement->getName())] = $movement;
        }

        $mainFlow = $this->flowWithoutScalingOptions($flow);
        $normalizedFlow = $this->normalizeMovementSearchText($mainFlow);

        foreach ($selectedMovementNames as $selectedMovementName) {
            $movement = $allowedMovementsByName[$this->normalizeMovementName($selectedMovementName)] ?? null;
            if (!$movement instanceof Movement) {
                continue;
            }

            if (!str_contains($normalizedFlow, $this->normalizeMovementSearchText($movement->getName()))) {
                throw new \RuntimeException(sprintf('OpenAI workout generation listed movement "%s" but did not include it in the workout flow.', $movement->getName()));
            }
        }
    }

    /**
     * @param Movement[] $mandatoryMovements
     */
    private function assertMandatoryMovementsAppearInFlow(array $mandatoryMovements, string $flow): void
    {
        $mainFlow = $this->flowWithoutScalingOptions($flow);
        $normalizedFlow = $this->normalizeMovementSearchText($mainFlow);

        foreach ($mandatoryMovements as $movement) {
            if (!str_contains($normalizedFlow, $this->normalizeMovementSearchText($movement->getName()))) {
                throw new \RuntimeException(sprintf('OpenAI workout generation did not include mandatory movement "%s" in the workout flow.', $movement->getName()));
            }
        }
    }

    /**
     * @param Movement[] $bannedMovements
     */
    private function assertBannedMovementsDoNotAppearInFlow(array $bannedMovements, string $flow): void
    {
        $mainFlow = $this->flowWithoutScalingOptions($flow);
        $normalizedFlow = $this->normalizeMovementSearchText($mainFlow);

        foreach ($bannedMovements as $movement) {
            if (str_contains($normalizedFlow, $this->normalizeMovementSearchText($movement->getName()))) {
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
        return preg_replace('/[^a-z0-9]+/', '', strtolower($text)) ?? '';
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
        $allowedMovementsByName = [];
        foreach (array_merge($mandatoryMovements, $candidateMovements) as $movement) {
            $allowedMovementsByName[$this->normalizeMovementName($movement->getName())] = $movement;
        }

        $selectedMovementsByName = [];
        foreach ($mandatoryMovements as $movement) {
            $selectedMovementsByName[$this->normalizeMovementName($movement->getName())] = $movement;
        }

        $matchedSelectedMovementCount = 0;
        $seenSelectedMovementNames = [];
        foreach ($selectedMovementNames as $selectedMovementName) {
            $normalizedSelectedMovementName = $this->normalizeMovementName($selectedMovementName);
            if (isset($seenSelectedMovementNames[$normalizedSelectedMovementName])) {
                throw new \RuntimeException(sprintf('OpenAI workout generation returned duplicate movement "%s".', $selectedMovementName));
            }
            $seenSelectedMovementNames[$normalizedSelectedMovementName] = true;

            $movement = $allowedMovementsByName[$normalizedSelectedMovementName] ?? null;
            if (!$movement instanceof Movement) {
                throw new \RuntimeException(sprintf('OpenAI workout generation returned unrecognized movement "%s".', $selectedMovementName));
            }
            ++$matchedSelectedMovementCount;
            $selectedMovementsByName[$this->normalizeMovementName($movement->getName())] = $movement;
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
