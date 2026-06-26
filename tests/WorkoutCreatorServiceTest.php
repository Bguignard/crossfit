<?php

namespace App\Tests;

use App\Entity\Workout\Enum\ImplementEnum;
use App\Entity\Workout\Enum\MovementDifficultyEnum;
use App\Entity\Workout\Enum\MovementTypeEnum;
use App\Entity\Workout\Enum\WorkoutMovementGenerationTypeEnum;
use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use App\Entity\Workout\Enum\WorkoutTypeEnum;
use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\MovementDifficulty;
use App\Entity\Workout\MovementType;
use App\Entity\Workout\WorkoutMovementGenerationType;
use App\Entity\Workout\WorkoutOrigin;
use App\Entity\Workout\WorkoutOriginName;
use App\Entity\Workout\WorkoutPrescriptionStandard;
use App\Entity\Workout\WorkoutType;
use App\Entity\WorkoutGeneration\WorkoutGeneration;
use App\Repository\Workout\MovementDifficultyRepositoryInterface;
use App\Repository\Workout\MovementRepositoryInterface;
use App\Repository\Workout\WorkoutPrescriptionStandardRepository;
use App\Services\Workout\ChatGPTApiKeyInterface;
use App\Services\Workout\MovementDifficultyService;
use App\Services\Workout\MovementService;
use App\Services\Workout\MovementServiceInterface;
use App\Services\Workout\MuscleServiceInterface;
use App\Services\Workout\WorkoutCreatorService;
use App\Services\Workout\WorkoutOriginServiceInterface;
use App\Services\Workout\WorkoutPrescriptionStandardPromptBuilder;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 */
class WorkoutCreatorServiceTest extends TestCase
{
    public function testWorkoutGenerationRejectsMovementThatIsBothMandatoryAndBanned(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $row = new Movement('Row', $difficulty, $cardio);

        $movementService = $this->createMock(MovementServiceInterface::class);
        $chatGpt = $this->createMock(ChatGPTApiKeyInterface::class);
        $chatGpt->expects(self::never())->method('getWorkoutFlowFromPrompt');
        $workoutOriginService = $this->createMock(WorkoutOriginServiceInterface::class);
        $workoutOriginService->expects(self::never())->method('getExistingOrInsertNewWorkoutOrigin');

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Contradictory movement filter test')
            ->setTimeCap(10)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setMandatoryMovements([$row])
            ->setBannedMovements([$row])
            ->setNumberOfDifferentMovements(1)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Movement "Row" cannot be both mandatory and banned.');

        (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);
    }

    public function testAmrapPromptIgnoresNumberOfRounds(): void
    {
        ['workout' => $workout, 'prompt' => $prompt] = $this->createWorkoutAndCapturePrompt(
            WorkoutTypeEnum::AMRAP,
            7,
        );

        self::assertNull($workout->getNumberOfRounds());
        self::assertStringContainsString('This workout is an AMRAP: create one repeatable movement sequence', $prompt);
        self::assertStringContainsString('Do not impose or mention a fixed number of rounds.', $prompt);
        self::assertStringContainsString('Time-cap calibration guidance: the requested time cap is 12 minutes.', $prompt);
        self::assertStringContainsString('usually around 75-95% of the time cap', $prompt);
        self::assertStringContainsString('one round must not be so tiny that the workout becomes meaningless churn', $prompt);
        self::assertStringContainsString('Movement diversity guidance: choose movements from the full allowed pool', $prompt);
        self::assertStringContainsString('must not be used as a default group simply because they are familiar benchmark movements', $prompt);
        self::assertStringNotContainsString('there is only one round', $prompt);
        self::assertStringNotContainsString('there are 1 rounds', $prompt);
        self::assertStringNotContainsString('7 rounds of', $prompt);
    }

    public function testNonAmrapPromptCanUseImposedNumberOfRounds(): void
    {
        ['workout' => $workout, 'prompt' => $prompt] = $this->createWorkoutAndCapturePrompt(
            WorkoutTypeEnum::FOR_TIME,
            3,
        );

        self::assertSame(3, $workout->getNumberOfRounds());
        self::assertStringContainsString('The athlete explicitly imposed the number of rounds.', $prompt);
        self::assertStringContainsString('3 rounds of', $prompt);
        self::assertStringContainsString('the imposed number of rounds (3 rounds)', $prompt);
    }

    public function testNonAmrapPromptCanLeaveRoundsToAi(): void
    {
        ['workout' => $workout, 'prompt' => $prompt] = $this->createWorkoutAndCapturePrompt(
            WorkoutTypeEnum::FOR_TIME,
            null,
        );

        self::assertNull($workout->getNumberOfRounds());
        self::assertStringContainsString('The athlete did not impose a number of rounds.', $prompt);
        self::assertStringContainsString('Choose the movement sequence, reps, distances, intervals and round structure', $prompt);
        self::assertStringNotContainsString('there are', $prompt);
    }

    public function testIntervalsPromptCanLeaveRoundsToAi(): void
    {
        ['workout' => $workout, 'prompt' => $prompt] = $this->createWorkoutAndCapturePrompt(
            WorkoutTypeEnum::INTERVALS,
            null,
        );

        self::assertNull($workout->getNumberOfRounds());
        self::assertStringContainsString('The workout pattern is an Intervals workout.', $prompt);
        self::assertStringContainsString('choose the number of intervals that best fits the stimulus and time cap', $prompt);
        self::assertStringNotContainsString('with  rounds', $prompt);
    }

    public function testWorkoutPromptDiscouragesDefaultBenchmarkMovementTrio(): void
    {
        ['prompt' => $prompt] = $this->createWorkoutAndCapturePrompt(
            WorkoutTypeEnum::FOR_TIME,
            4,
        );

        self::assertStringContainsString('Movement diversity guidance: choose movements from the full allowed pool', $prompt);
        self::assertStringContainsString('Wall Ball Shot, Chest to Bar Pull Up, Thruster, Box Jump and Box Jump Over are allowed', $prompt);
        self::assertStringContainsString('must not be used as a default group simply because they are familiar benchmark movements', $prompt);
        self::assertStringContainsString('avoid building the whole workout around only the classic wall-ball / thruster / pull-up-bar / box-jump pattern', $prompt);
    }

    public function testOneMovementWorkoutPromptSkipsInteractionStrategyGuidance(): void
    {
        ['prompt' => $prompt] = $this->createWorkoutAndCapturePrompt(
            WorkoutTypeEnum::FOR_TIME,
            null,
            'Strength',
            1,
        );

        self::assertStringContainsString('Choose exactly 1 different movement for the final workout.', $prompt);
        self::assertStringNotContainsString('Movement interaction strategy guidance', $prompt);
        self::assertStringNotContainsString('pair movements that interfere little', $prompt);
        self::assertStringNotContainsString('alternate movement demands', $prompt);
        self::assertStringNotContainsString('pre-fatigue the next movement', $prompt);
    }

    public function testPureStrengthPromptSkipsMetconInteractionStrategyGuidance(): void
    {
        ['prompt' => $prompt] = $this->createWorkoutAndCapturePrompt(
            WorkoutTypeEnum::FOR_TIME,
            null,
            'Strength',
            2,
        );

        self::assertStringContainsString('Strength: write this like a true strength prescription', $prompt);
        self::assertStringContainsString('Choose exactly 2 different movements for the final workout.', $prompt);
        self::assertStringNotContainsString('Movement interaction strategy guidance', $prompt);
        self::assertStringNotContainsString('pair movements that interfere little', $prompt);
        self::assertStringNotContainsString('alternate movement demands', $prompt);
        self::assertStringNotContainsString('pre-fatigue the next movement', $prompt);
    }

    public function testCompetitionPromptUsesMovementFrequencyGuidanceFilteredByAllowedPool(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $gymnastic = new MovementType(MovementTypeEnum::GYMNASTIC);
        $weightlifting = new MovementType(MovementTypeEnum::WEIGHTLIFTING);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $chestToBarPullUp = new Movement('Chest to Bar Pull Up', $difficulty, $gymnastic);
        $toesToBar = new Movement('Toes to Bar', $difficulty, $gymnastic);
        $doubleUnder = new Movement('Double Under', $difficulty, $cardio);
        $wallBallShot = new Movement('Wall Ball Shot', $difficulty, $cardio);
        $thruster = new Movement('Thruster', $difficulty, $weightlifting);
        $skiErg = new Movement('Ski Erg', $difficulty, $cardio);
        $powerClean = new Movement('Power Clean', $difficulty, $weightlifting);
        $row = new Movement('Row', $difficulty, $cardio);

        $movementService = new class([$chestToBarPullUp, $toesToBar, $doubleUnder, $wallBallShot, $thruster, $skiErg, $powerClean, $row]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public string $prompt = '';

            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                $this->prompt = $prompt;

                return json_encode([
                    'flow' => "For time:\n21-15-9\nDouble Under\nToes to Bar\nSki Erg",
                    'scalingOptions' => "RX: as written\nIntermediate: reduce T2B volume\nScaled: knees raises",
                    'movements' => ['Double Under', 'Toes to Bar', 'Ski Erg'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Competition guidance test')
            ->setStimulus('Competition')
            ->setStimulusIntent('Tester plusieurs qualités avec une vraie gestion de compétition.')
            ->setTimeCap(15)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::FOR_TIME))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$gymnastic, $weightlifting, $cardio])
            ->setNumberOfDifferentMovements(3)
            ->setNumberOfRounds(3)
            ->setIsTeamWorkout(false);

        (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        self::assertStringContainsString('Competition movement recurrence guidance:', $chatGpt->prompt);
        self::assertStringContainsString('very frequent available movements: Toes to Bar, Double Under, Wall Ball Shot.', $chatGpt->prompt);
        self::assertStringContainsString('regular available movements: Chest to Bar Pull Up, Power Clean, Ski Erg.', $chatGpt->prompt);
        self::assertStringContainsString('Do not default to Thruster + Chest to Bar Pull Up', $chatGpt->prompt);
        self::assertStringContainsString('Recent generated competition workouts are overusing this cluster: Power Clean, Wall Ball Shot, Row, Chest to Bar Pull Up.', $chatGpt->prompt);
        self::assertStringContainsString('choose at most two from that cluster unless one of them is mandatory', $chatGpt->prompt);
        self::assertStringContainsString('Strong rotation rule for this generation: no movement is mandatory, so choose at most one from these currently overused generated anchors: Power Clean, Chest to Bar Pull Up, Wall Ball Shot, Thruster.', $chatGpt->prompt);
        self::assertStringContainsString('Power Clean + Chest to Bar Pull Up is recurring too often', $chatGpt->prompt);
        self::assertStringContainsString('Do not select both together unless the user explicitly forced both movements.', $chatGpt->prompt);
        self::assertStringContainsString('Chest to Bar Pull Up + Thruster', $chatGpt->prompt);
        self::assertStringContainsString('Double Under + Toes to Bar', $chatGpt->prompt);
        self::assertStringContainsString('Movement interaction strategy guidance', $chatGpt->prompt);
        self::assertStringContainsString('internal strategy', $chatGpt->prompt);
        self::assertStringContainsString('invisible to the athlete', $chatGpt->prompt);
        self::assertStringContainsString('The final workout should', $chatGpt->prompt);
        self::assertStringNotContainsString('Muscle Up + Toes to Bar', $chatGpt->prompt);
        self::assertStringNotContainsString('regular available movements: Chest to Bar Pull Up, Box Jump Over', $chatGpt->prompt);
        self::assertStringNotContainsString('Box Jump Over +', $chatGpt->prompt);
    }

    public function testCompetitionGenerationRetriesRejectedOpenStyleCluster(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $gymnastic = new MovementType(MovementTypeEnum::GYMNASTIC);
        $weightlifting = new MovementType(MovementTypeEnum::WEIGHTLIFTING);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $row = new Movement('Row', $difficulty, $cardio);
        $chestToBarPullUp = new Movement('Chest to Bar Pull Up', $difficulty, $gymnastic);
        $thruster = new Movement('Thruster', $difficulty, $weightlifting);
        $toesToBar = new Movement('Toes to Bar', $difficulty, $gymnastic);
        $powerSnatch = new Movement('Power Snatch', $difficulty, $weightlifting);

        $chatGpt = new class([['flow' => "For time:\n3 rounds of:\n- 15/12 cal Row\n- 12 Chest to Bar Pull Up\n- 9 Thruster (61/43 kg)", 'scalingOptions' => "RX: as written\nIntermediate: reduce reps\nScaled: jumping pull-ups and light thrusters", 'movements' => ['Row', 'Chest to Bar Pull Up', 'Thruster']], ['flow' => "For time:\n3 rounds of:\n- 15/12 cal Row\n- 12 Toes to Bar\n- 9 Power Snatch (43/30 kg)", 'scalingOptions' => "RX: as written\nIntermediate: reduce load\nScaled: knees raises and hang power snatches", 'movements' => ['Row', 'Toes to Bar', 'Power Snatch']]]) implements ChatGPTApiKeyInterface {
            public int $calls = 0;
            public array $prompts = [];

            public function __construct(private readonly array $responses)
            {
            }

            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                $this->prompts[] = $prompt;

                return json_encode($this->responses[$this->calls++], JSON_THROW_ON_ERROR);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Competition rejected cluster retry')
            ->setStimulus('Competition')
            ->setStimulusIntent('Tester plusieurs qualités simultanément.')
            ->setTimeCap(15)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::FOR_TIME))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$gymnastic, $weightlifting, $cardio])
            ->setNumberOfDifferentMovements(3)
            ->setNumberOfRounds(3)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService(
            $this->movementServiceReturning([$row, $chestToBarPullUp, $thruster, $toesToBar, $powerSnatch]),
            $chatGpt,
            $this->workoutOriginService(),
        ))->createWorkout($workoutGeneration);

        self::assertSame(2, $chatGpt->calls);
        self::assertStringContainsString('Previous generation rejected', $chatGpt->prompts[1]);
        self::assertStringContainsString('Chest to Bar Pull Up + Thruster + Row', $chatGpt->prompts[1]);
        self::assertSame(['Row', 'Toes to Bar', 'Power Snatch'], array_map(
            static fn (Movement $movement): ?string => $movement->getName(),
            $workout->getMovements()->toArray()
        ));
    }

    public function testCompetitionGenerationAllowsRejectedClusterWhenUserForcedMovements(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $gymnastic = new MovementType(MovementTypeEnum::GYMNASTIC);
        $weightlifting = new MovementType(MovementTypeEnum::WEIGHTLIFTING);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $row = new Movement('Row', $difficulty, $cardio);
        $chestToBarPullUp = new Movement('Chest to Bar Pull Up', $difficulty, $gymnastic);
        $thruster = new Movement('Thruster', $difficulty, $weightlifting);

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public int $calls = 0;

            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                ++$this->calls;

                return json_encode([
                    'flow' => "For time:\n3 rounds of:\n- 15/12 cal Row\n- 12 Chest to Bar Pull Up\n- 9 Thruster (61/43 kg)",
                    'scalingOptions' => "RX: as written\nIntermediate: reduce reps\nScaled: jumping pull-ups and light thrusters",
                    'movements' => ['Row', 'Chest to Bar Pull Up', 'Thruster'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Competition forced cluster')
            ->setStimulus('Competition')
            ->setStimulusIntent('Tester plusieurs qualités simultanément.')
            ->setTimeCap(15)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::FOR_TIME))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$gymnastic, $weightlifting, $cardio])
            ->setMandatoryMovements([$row, $chestToBarPullUp, $thruster])
            ->setNumberOfDifferentMovements(3)
            ->setNumberOfRounds(3)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService(
            $this->movementServiceReturning([$row, $chestToBarPullUp, $thruster]),
            $chatGpt,
            $this->workoutOriginService(),
        ))->createWorkout($workoutGeneration);

        self::assertSame(1, $chatGpt->calls);
        self::assertSame(['Row', 'Chest to Bar Pull Up', 'Thruster'], array_map(
            static fn (Movement $movement): ?string => $movement->getName(),
            $workout->getMovements()->toArray()
        ));
    }

    public function testCompetitionPromptUsesCuratedMovementSlateWithAtMostOneOverusedAnchor(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $gymnastic = new MovementType(MovementTypeEnum::GYMNASTIC);
        $weightlifting = new MovementType(MovementTypeEnum::WEIGHTLIFTING);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $strongman = new MovementType(MovementTypeEnum::STRONGMAN);
        $plyometric = new MovementType(MovementTypeEnum::PLYOMETRIC);
        $movements = [
            new Movement('Row', $difficulty, $cardio),
            new Movement('Chest to Bar Pull Up', $difficulty, $gymnastic),
            new Movement('Thruster', $difficulty, $weightlifting),
            new Movement('Wall Ball Shot', $difficulty, $cardio),
            new Movement('Power Clean', $difficulty, $weightlifting),
            new Movement('Toes to Bar', $difficulty, $gymnastic),
            new Movement('Double Under', $difficulty, $cardio),
            new Movement('Ski Erg', $difficulty, $cardio),
            new Movement('Power Snatch', $difficulty, $weightlifting),
            new Movement('Burpee Box Jump Over', $difficulty, $plyometric),
            new Movement('Sandbag Carry', $difficulty, $strongman),
            new Movement('Bike Erg', $difficulty, $cardio),
        ];
        $workoutGeneration = $this->competitionWorkoutGeneration($difficulty, [$gymnastic, $weightlifting, $cardio, $strongman, $plyometric]);
        $service = new WorkoutCreatorService(
            $this->movementServiceReturning($movements),
            $this->createMock(ChatGPTApiKeyInterface::class),
            $this->workoutOriginService(),
        );

        $slate = $this->candidateMovementsForPrompt($service, $workoutGeneration, $movements);
        $slateNames = array_map(static fn (Movement $movement): ?string => $movement->getName(), $slate);
        $overusedAnchors = array_intersect($slateNames, ['Power Clean', 'Chest to Bar Pull Up', 'Wall Ball Shot', 'Thruster']);

        self::assertLessThan(count($movements), count($slate));
        self::assertGreaterThanOrEqual($workoutGeneration->getNumberOfDifferentMovements(), count($slate));
        self::assertLessThanOrEqual(1, count($overusedAnchors));
        self::assertContains('Sandbag Carry', $slateNames);
    }

    public function testWorkoutGenerationRejectsMovementOutsideCuratedCompetitionSlate(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $gymnastic = new MovementType(MovementTypeEnum::GYMNASTIC);
        $weightlifting = new MovementType(MovementTypeEnum::WEIGHTLIFTING);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $strongman = new MovementType(MovementTypeEnum::STRONGMAN);
        $plyometric = new MovementType(MovementTypeEnum::PLYOMETRIC);
        $movements = [
            new Movement('Row', $difficulty, $cardio),
            new Movement('Chest to Bar Pull Up', $difficulty, $gymnastic),
            new Movement('Thruster', $difficulty, $weightlifting),
            new Movement('Wall Ball Shot', $difficulty, $cardio),
            new Movement('Power Clean', $difficulty, $weightlifting),
            new Movement('Toes to Bar', $difficulty, $gymnastic),
            new Movement('Double Under', $difficulty, $cardio),
            new Movement('Ski Erg', $difficulty, $cardio),
            new Movement('Power Snatch', $difficulty, $weightlifting),
            new Movement('Burpee Box Jump Over', $difficulty, $plyometric),
            new Movement('Sandbag Carry', $difficulty, $strongman),
            new Movement('Bike Erg', $difficulty, $cardio),
        ];
        $workoutGeneration = $this->competitionWorkoutGeneration($difficulty, [$gymnastic, $weightlifting, $cardio, $strongman, $plyometric]);
        $service = new WorkoutCreatorService(
            $this->movementServiceReturning($movements),
            $this->createMock(ChatGPTApiKeyInterface::class),
            $this->workoutOriginService(),
        );
        $slateNames = array_map(
            static fn (Movement $movement): ?string => $movement->getName(),
            $this->candidateMovementsForPrompt($service, $workoutGeneration, $movements)
        );
        $outsideSlateMovement = null;
        foreach ($movements as $movement) {
            if (!in_array($movement->getName(), $slateNames, true)) {
                $outsideSlateMovement = $movement;
                break;
            }
        }

        self::assertInstanceOf(Movement::class, $outsideSlateMovement);

        $fallbackMovements = array_slice($slateNames, 0, 2);
        $chatGpt = new class($outsideSlateMovement->getName(), $fallbackMovements) implements ChatGPTApiKeyInterface {
            public function __construct(private readonly string $outsideSlateMovementName, private readonly array $fallbackMovementNames)
            {
            }

            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => sprintf("For time:\n- 10 %s\n- 10 %s\n- 10 %s", $this->outsideSlateMovementName, $this->fallbackMovementNames[0], $this->fallbackMovementNames[1]),
                    'scalingOptions' => "RX: as written\nIntermediate: reduce reps\nScaled: reduce reps further",
                    'movements' => [$this->outsideSlateMovementName, $this->fallbackMovementNames[0], $this->fallbackMovementNames[1]],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(sprintf('OpenAI workout generation returned unrecognized movement "%s".', $outsideSlateMovement->getName()));

        (new WorkoutCreatorService(
            $this->movementServiceReturning($movements),
            $chatGpt,
            $this->workoutOriginService(),
        ))->createWorkout($workoutGeneration);
    }

    public function testOpenAiChoosesMovementsFromTheCompletePossiblePool(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $run = new Movement('Run', $difficulty, $cardio);
        $row = new Movement('Row', $difficulty, $cardio);
        $burpee = new Movement('Burpee', $difficulty, $cardio);
        $boxJump = new Movement('Box Jump', $difficulty, $cardio);

        $movementService = new class([$run, $row, $burpee]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public string $prompt = '';

            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                $this->prompt = $prompt;

                return json_encode([
                    'flow' => "For time:\n1000 m Run\n50 Burpees",
                    'scalingOptions' => "RX: as written\nIntermediate: 800 m Run and 35 Burpees\nScaled: 600 m Run and 25 Burpees",
                    'movements' => ['Run', 'Burpee'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Engine test')
            ->setStimulus('Engine long')
            ->setStimulusIntent('Volume soutenu, respiration stable, gestion du pacing.')
            ->setTimeCap(30)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::FOR_TIME))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setBannedMovements([$boxJump])
            ->setNumberOfDifferentMovements(2)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        self::assertStringContainsString('Candidate movement pool', $chatGpt->prompt);
        self::assertStringContainsString('The workout examples are format references only', $chatGpt->prompt);
        self::assertStringContainsString('- Run', $chatGpt->prompt);
        self::assertStringContainsString('- Row', $chatGpt->prompt);
        self::assertStringContainsString('- Burpee', $chatGpt->prompt);
        self::assertStringContainsString('Banned movements that must not appear in the workout flow', $chatGpt->prompt);
        self::assertStringContainsString('- Box Jump', $chatGpt->prompt);
        self::assertStringContainsString('Level prescription guidance: create an Intermediate version', $chatGpt->prompt);
        self::assertStringContainsString('Stimulus-specific guidance:', $chatGpt->prompt);
        self::assertStringContainsString('Engine: make the limitation primarily aerobic', $chatGpt->prompt);
        self::assertStringContainsString('Never infer, invent or borrow unavailable equipment', $chatGpt->prompt);
        self::assertStringContainsString('check every selected movement against the printed pool', $chatGpt->prompt);
        self::assertStringContainsString('Do not choose Wall Ball Shot, sled, sandbag, dumbbell or other equipment-specific movements unless their required implement is explicitly printed under that exact movement', $chatGpt->prompt);
        self::assertStringContainsString('always include level-appropriate male/female loads in kg', $chatGpt->prompt);
        self::assertStringContainsString('Every loaded movement written in the main workout flow must include either kg loads', $chatGpt->prompt);
        self::assertStringNotContainsString('185/135 lb', $chatGpt->prompt);
        self::assertStringContainsString('Scaling options', $chatGpt->prompt);
        self::assertStringContainsString('"scalingOptions"', $chatGpt->prompt);
        self::assertStringContainsString('The movements array must contain exactly 2 unique movement name(s)', $chatGpt->prompt);
        self::assertStringContainsString('with no duplicates, using only exact names from the allowed lists', $chatGpt->prompt);
        self::assertStringContainsString('The flow field must contain only the main workout prescription', $chatGpt->prompt);
        self::assertStringContainsString('Put all substitutions and adaptations only in scalingOptions', $chatGpt->prompt);
        self::assertStringContainsString('the movements array must contain exactly the movement names used in the main workout flow', strtolower($chatGpt->prompt));
        self::assertStringContainsString('Team workout guidance: this is an individual workout', $chatGpt->prompt);
        self::assertStringContainsString('Do not use partner relay, shared reps, split-anyhow rules, synchronized work', $chatGpt->prompt);
        self::assertStringContainsString('partner holds/carries/static constraints', $chatGpt->prompt);
        self::assertStringNotContainsString('Do not use partner relay, shared reps, split-anyhow rules, synchronized work, holds, carries', $chatGpt->prompt);
        self::assertStringContainsString('Movement A', $chatGpt->prompt);
        self::assertStringNotContainsString('25 Pull-Ups', $chatGpt->prompt);
        self::assertStringNotContainsString('10 thrusters', $chatGpt->prompt);
        self::assertStringNotContainsString('10 chest-to-bar pull-ups', $chatGpt->prompt);
        self::assertStringContainsString("Scaling options:\nRX: as written", $workout->getFlow());
        self::assertSame(['Run', 'Burpee'], array_map(
            static fn (Movement $movement): ?string => $movement->getName(),
            $workout->getMovements()->toArray()
        ));
    }

    public function testMovementFilteringRemovesMovementsWhoseRequiredImplementIsUnavailable(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $rower = new Implement(ImplementEnum::ROWER, null);
        $medicineBall = new Implement(ImplementEnum::MEDICINE_BALL, null);
        $row = (new Movement('Row', $difficulty, $cardio))->addPossibleImplement($rower);
        $wallBallShot = (new Movement('Wall Ball Shot', $difficulty, $cardio))->addPossibleImplement($medicineBall);
        $burpee = new Movement('Burpee', $difficulty, $cardio);

        $movementService = new MovementService(
            $this->createMock(MovementRepositoryInterface::class),
            new MovementDifficultyService($this->createMock(MovementDifficultyRepositoryInterface::class)),
            $this->createMock(MuscleServiceInterface::class),
        );

        $filteredMovements = $movementService->removeNotAvailableImplementsFromMovementsOfWorkout(
            new ArrayCollection([$rower]),
            [$row, $wallBallShot, $burpee],
        );

        self::assertSame(['Row', 'Burpee'], array_map(
            static fn (Movement $movement): ?string => $movement->getName(),
            $filteredMovements,
        ));
        self::assertSame([$rower], $row->getPossibleImplements()->toArray());
        self::assertSame([], $burpee->getPossibleImplements()->toArray());
    }

    public function testStimulusGuidanceCoversPostAuditCorrections(): void
    {
        $creator = new WorkoutCreatorService(
            $this->createMock(MovementServiceInterface::class),
            $this->createMock(ChatGPTApiKeyInterface::class),
            $this->createMock(WorkoutOriginServiceInterface::class),
        );
        $extractGuidance = new \ReflectionMethod(WorkoutCreatorService::class, 'stimulusSpecificGuidance');

        $strengthGuidance = $extractGuidance->invoke($creator, (new WorkoutGeneration())->setStimulus('Strength'));
        self::assertStringContainsString("Do not write 'Intervals X rounds' for pure strength work", $strengthGuidance);
        self::assertStringContainsString('compact set x rep prescription lines', $strengthGuidance);

        $engineGuidance = $extractGuidance->invoke($creator, (new WorkoutGeneration())->setStimulus('Engine'));
        self::assertStringContainsString('Avoid grip-heavy, high-skill gymnastics, and high-rep loaded stations', $engineGuidance);
        self::assertStringContainsString('Do not choose Wall Ball Shot, sled, sandbag, dumbbell or other equipment-specific movements unless their required implement is explicitly printed under that exact movement', $engineGuidance);

        $hyroxGuidance = $extractGuidance->invoke($creator, (new WorkoutGeneration())->setStimulus('Entrainement Hyrox'));
        self::assertStringContainsString('Prefer an alternating sequence such as run/erg, station, run/erg, station', $hyroxGuidance);
        self::assertStringContainsString('include at least two run/erg exposures', $hyroxGuidance);
        self::assertStringContainsString("do not write '1 rounds of' or '1 round of'", $hyroxGuidance);
        self::assertStringContainsString('usually 4-6 station movements', $hyroxGuidance);

        $fullHyroxGuidance = $extractGuidance->invoke($creator, (new WorkoutGeneration())->setStimulus('Simulation Hyrox'));
        self::assertStringContainsString('8 ordered functional stations', $fullHyroxGuidance);
        self::assertStringContainsString('run segment, station 1, run segment, station 2', $fullHyroxGuidance);
        self::assertStringContainsString('men/women standards or scaling options', $fullHyroxGuidance);
        self::assertStringNotContainsString('usually 4-6 station movements', $fullHyroxGuidance);

        $gymnasticsGuidance = $extractGuidance->invoke($creator, (new WorkoutGeneration())->setStimulus('Gymnastics / Skill'));
        self::assertStringContainsString('Use small sets and clear rest when using muscle-ups, HSPU, handstand walk or toes-to-bar', $gymnasticsGuidance);
        self::assertStringContainsString('avoid combining high totals of muscle-ups, HSPU and toes-to-bar in the same workout', $gymnasticsGuidance);
    }

    public function testOpenAiCanSuggestWorkoutVariantsBeforeFinalGeneration(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $row = new Movement('Row', $difficulty, $cardio);
        $burpee = new Movement('Burpee', $difficulty, $cardio);

        $movementService = new class([$row, $burpee]) implements MovementServiceInterface {
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public string $prompt = '';

            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                $this->prompt = $prompt;

                return json_encode([
                    'variants' => [
                        [
                            'title' => 'Engine progressif',
                            'intent' => 'Tenir un rythme respiratoire stable.',
                            'format' => 'AMRAP 16',
                            'movementNames' => ['Row', 'Burpee'],
                            'summary' => 'Un choix simple pour tester le pacing.',
                        ],
                    ],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = $this->createMock(WorkoutOriginServiceInterface::class);
        $workoutOriginService->expects(self::never())->method('getExistingOrInsertNewWorkoutOrigin');

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Variant test')
            ->setStimulus('Engine long')
            ->setStimulusIntent('Volume soutenu, respiration stable, gestion du pacing.')
            ->setTimeCap(16)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setNumberOfDifferentMovements(2)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $variants = (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkoutVariants($workoutGeneration);

        self::assertSame('Engine progressif', $variants[0]['title']);
        self::assertSame(['Row', 'Burpee'], $variants[0]['movementNames']);
        self::assertStringContainsString('Suggest 3 distinct CrossFit workout concepts before generating a final workout.', $chatGpt->prompt);
        self::assertStringContainsString('Stimulus-specific guidance:', $chatGpt->prompt);
        self::assertStringContainsString('Engine: make the limitation primarily aerobic', $chatGpt->prompt);
        self::assertStringContainsString('Movement interaction strategy guidance', $chatGpt->prompt);
        self::assertStringContainsString('Each concept should', $chatGpt->prompt);
        self::assertStringNotContainsString('"skill_under_fatigue"', $chatGpt->prompt);
        self::assertStringNotContainsString('"same_limiter"', $chatGpt->prompt);
        self::assertStringContainsString('Movement diversity guidance: choose movements from the full allowed pool', $chatGpt->prompt);
        self::assertStringContainsString('must not be used as a default group simply because they are familiar benchmark movements', $chatGpt->prompt);
        self::assertStringContainsString('Do not write the final workout flow yet.', $chatGpt->prompt);
    }

    public function testTeamWorkoutVariantPromptUsesConceptGuidanceWithoutFinalFlowInstruction(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::RX);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $row = new Movement('Row', $difficulty, $cardio);
        $burpee = new Movement('Burpee', $difficulty, $cardio);

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public string $prompt = '';

            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                $this->prompt = $prompt;

                return json_encode([
                    'variants' => [
                        [
                            'title' => 'Relais court',
                            'intent' => 'Alterner des relais courts sans attente longue.',
                            'format' => 'Team of 2, intervals courts',
                            'movementNames' => ['Row', 'Burpee'],
                            'summary' => 'Un concept team avec calories partagees et relais rapides.',
                        ],
                    ],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Team variant test')
            ->setStimulus('Engine')
            ->setStimulusIntent('Maintenir une respiration stable en equipe.')
            ->setTimeCap(18)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setNumberOfDifferentMovements(2)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(true);

        $variants = (new WorkoutCreatorService(
            $this->movementServiceReturning([$row, $burpee]),
            $chatGpt,
            $this->createMock(WorkoutOriginServiceInterface::class),
        ))->createWorkoutVariants($workoutGeneration);

        self::assertSame('Relais court', $variants[0]['title']);
        self::assertStringContainsString('Team workout concept guidance', $chatGpt->prompt);
        self::assertStringContainsString('Do not write the final workout flow yet', $chatGpt->prompt);
        self::assertStringContainsString('describe the chosen team structure in the concept intent, format or summary', $chatGpt->prompt);
        self::assertStringContainsString('Central "you go, I go" constraint for concepts: short relays only', $chatGpt->prompt);
        self::assertStringNotContainsString('then write the flow so the work-sharing rule is impossible to miss', $chatGpt->prompt);
    }

    public function testTeamWorkoutPromptRequiresExplicitTeamStructure(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::RX);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $run = new Movement('Run', $difficulty, $cardio);
        $row = new Movement('Row', $difficulty, $cardio);

        $movementService = new class([$run, $row]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public string $prompt = '';

            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                $this->prompt = $prompt;

                return json_encode([
                    'flow' => "Team of 2, for time:\nYou go, I go rounds\n400 m Run\n500 m Row",
                    'scalingOptions' => "RX: as written\nIntermediate: reduce row pace\nScaled: 250 m Run and 350 m Row",
                    'movements' => ['Run', 'Row'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Team engine test')
            ->setTimeCap(24)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::FOR_TIME))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setNumberOfDifferentMovements(2)
            ->setNumberOfRounds(4)
            ->setIsTeamWorkout(true);

        (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        self::assertStringContainsString('Team workout: yes', $chatGpt->prompt);
        self::assertStringContainsString('this must be explicitly written as a team workout', $chatGpt->prompt);
        self::assertStringContainsString('team-of-2', $chatGpt->prompt);
        self::assertStringContainsString('Team structure taxonomy available for this generation', $chatGpt->prompt);
        self::assertStringContainsString('synchronized block', $chatGpt->prompt);
        self::assertStringContainsString('shared total reps/calories, split anyhow', $chatGpt->prompt);
        self::assertStringContainsString('partner alternating rounds', $chatGpt->prompt);
        self::assertStringContainsString('active hold/carry/static constraint while partner works', $chatGpt->prompt);
        self::assertStringContainsString('Pick exactly one main structure', $chatGpt->prompt);
        self::assertStringContainsString('you go, I go', $chatGpt->prompt);
        self::assertStringContainsString('Central "you go, I go" constraint: use short relays only', $chatGpt->prompt);
        self::assertStringContainsString('Do not prescribe long row/run segments, full long stations, large unbroken sets or whole long rounds as "you go, I go"', $chatGpt->prompt);
        self::assertStringContainsString('split it into short distance/repetition/calorie switches', $chatGpt->prompt);
        self::assertStringContainsString('do not synchronize the entire workout if that breaks the stimulus', $chatGpt->prompt);
        self::assertStringContainsString('state explicitly whether athletes share one machine', $chatGpt->prompt);
        self::assertStringContainsString('Time-cap calibration guidance: the requested time cap is 24 minutes.', $chatGpt->prompt);
        self::assertStringContainsString('For-time workouts: calibrate reps, distances, loads, round count and transitions', $chatGpt->prompt);
        self::assertStringContainsString('For team workouts, account for shared reps, split-anyhow work, synchronized reps, partner changes and machine sharing.', $chatGpt->prompt);
        self::assertStringContainsString('increase total team volume or add meaningful synchronization/holding constraints', $chatGpt->prompt);
    }

    public function testWorkoutFlowDoesNotDuplicateScalingSectionWhenModelUsesShortHeading(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $run = new Movement('Run', $difficulty, $cardio);

        $movementService = new class([$run]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "AMRAP 12 minutes\n400 m Run\n\nScaling:\nRX: as written\nIntermediate: 300 m Run\nScaled: 200 m Run",
                    'scalingOptions' => "RX: as written\nIntermediate: 300 m Run\nScaled: 200 m Run",
                    'movements' => ['Run'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Scaling heading test')
            ->setTimeCap(12)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setNumberOfDifferentMovements(1)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        self::assertStringContainsString("Scaling:\nRX: as written", $workout->getFlow());
        self::assertStringNotContainsString("\n\nScaling options:\nRX: as written", $workout->getFlow());
    }

    public function testWorkoutGenerationAcceptsScalingOptionsWhenOnlyPresentInFlow(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $row = new Movement('Row', $difficulty, $cardio);

        $movementService = new class([$row]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "For time:\n1000 m Row\n\nScaling options:\nRX: as written\nIntermediate: 800 m Row\nScaled: 600 m Row",
                    'scalingOptions' => '',
                    'movements' => ['Row'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Scaling fallback test')
            ->setTimeCap(12)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::FOR_TIME))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setNumberOfDifferentMovements(1)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        self::assertStringContainsString("Scaling options:\nRX: as written", $workout->getFlow());
        self::assertStringContainsString('Intermediate: 800 m Row', $workout->getFlow());
    }

    public function testWorkoutGenerationDoesNotDuplicateScalingOptionsHeadingFromPayload(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $row = new Movement('Row', $difficulty, $cardio);

        $movementService = new class([$row]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "For time:\n1000 m Row",
                    'scalingOptions' => "Scaling options:\nRX: as written\nIntermediate: 800 m Row\nScaled: 600 m Row",
                    'movements' => ['Row'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Scaling heading from payload test')
            ->setTimeCap(12)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::FOR_TIME))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setNumberOfDifferentMovements(1)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        self::assertStringContainsString("Scaling options:\nRX: as written", $workout->getFlow());
        self::assertStringNotContainsString("Scaling options:\nScaling options:", $workout->getFlow());
    }

    public function testWorkoutGenerationNormalizesDuplicateScalingOptionsHeadingInFlow(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $row = new Movement('Row', $difficulty, $cardio);

        $movementService = new class([$row]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "For time:\n1000 m Row\n\nScaling options:\nScaling options:\nRX: as written\nIntermediate: 800 m Row\nScaled: 600 m Row",
                    'scalingOptions' => "RX: as written\nIntermediate: 800 m Row\nScaled: 600 m Row",
                    'movements' => ['Row'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Duplicate scaling heading in flow test')
            ->setTimeCap(12)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::FOR_TIME))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setNumberOfDifferentMovements(1)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        self::assertStringContainsString("Scaling options:\nRX: as written", $workout->getFlow());
        self::assertStringNotContainsString("Scaling options:\nScaling options:", $workout->getFlow());
    }

    public function testScalingOptionsRecoveredFromFlowStopBeforeFollowingSections(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $row = new Movement('Row', $difficulty, $cardio);

        $movementService = new class([$row]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public string $prompt = '';

            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                $this->prompt = $prompt;

                return json_encode([
                    'flow' => "For time:\n1000 m Row\n\nScaling options:\nRX: as written\nIntermediate: 800 m Row\nScaled: 600 m Row\n\nCoach notes:\nKeep stroke rate smooth.",
                    'scalingOptions' => '',
                    'movements' => ['Row'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Scaling section boundary test')
            ->setTimeCap(12)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::FOR_TIME))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setNumberOfDifferentMovements(1)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $creator = new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService);
        $workout = $creator->createWorkout($workoutGeneration);

        self::assertStringContainsString("Scaling options:\nRX: as written", $workout->getFlow());
        self::assertStringContainsString("Coach notes:\nKeep stroke rate smooth.", $workout->getFlow());

        $extractScaling = new \ReflectionMethod(WorkoutCreatorService::class, 'scalingOptionsFromFlow');
        self::assertSame(
            "RX: as written\nIntermediate: 800 m Row\nScaled: 600 m Row",
            $extractScaling->invoke($creator, $workout->getFlow())
        );
    }

    public function testWorkoutGenerationAcceptsSelectedMovementsAsStringList(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $run = new Movement('Run', $difficulty, $cardio);
        $burpee = new Movement('Burpee', $difficulty, $cardio);

        $movementService = new class([$run, $burpee]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "For time:\n400 m Run\n20 Burpees",
                    'scalingOptions' => "RX: as written\nIntermediate: 300 m Run and 15 Burpees\nScaled: 200 m Run and 10 Burpees",
                    'movements' => 'Run, Burpee',
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('String movement list test')
            ->setTimeCap(12)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::FOR_TIME))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setNumberOfDifferentMovements(2)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        self::assertSame(['Run', 'Burpee'], array_map(
            static fn (Movement $movement): ?string => $movement->getName(),
            $workout->getMovements()->toArray()
        ));
    }

    public function testWorkoutGenerationAcceptsSelectedMovementsAsObjectList(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $row = new Movement('Row', $difficulty, $cardio);
        $burpee = new Movement('Burpee', $difficulty, $cardio);

        $movementService = new class([$row, $burpee]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "AMRAP 10 minutes\n250 m Row\n10 Burpees",
                    'scalingOptions' => "RX: as written\nIntermediate: 200 m Row and 8 Burpees\nScaled: 150 m Row and 6 Burpees",
                    'movements' => [
                        ['name' => 'Row'],
                        ['name' => 'Burpee'],
                    ],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Object movement list test')
            ->setTimeCap(10)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setNumberOfDifferentMovements(2)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        self::assertSame(['Row', 'Burpee'], array_map(
            static fn (Movement $movement): ?string => $movement->getName(),
            $workout->getMovements()->toArray()
        ));
    }

    public function testWorkoutGenerationRejectsUnrecognizedGeneratedMovementNames(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $row = new Movement('Row', $difficulty, $cardio);

        $movementService = new class([$row]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "AMRAP 10 minutes\n250 m Ski",
                    'scalingOptions' => "RX: as written\nIntermediate: 200 m Ski\nScaled: 150 m Ski",
                    'movements' => ['Ski'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Unrecognized generated movement test')
            ->setTimeCap(10)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setNumberOfDifferentMovements(1)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OpenAI workout generation returned unrecognized movement "Ski".');

        (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);
    }

    public function testWorkoutGenerationRejectsMixedUnrecognizedGeneratedMovementNames(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $row = new Movement('Row', $difficulty, $cardio);
        $burpee = new Movement('Burpee', $difficulty, $cardio);

        $movementService = new class([$row, $burpee]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "AMRAP 10 minutes\n250 m Row\n250 m Ski",
                    'scalingOptions' => "RX: as written\nIntermediate: 200 m Row and 200 m Ski\nScaled: 150 m Row and 150 m Ski",
                    'movements' => ['Row', 'Ski'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Mixed unrecognized generated movement test')
            ->setTimeCap(10)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setNumberOfDifferentMovements(2)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OpenAI workout generation returned unrecognized movement "Ski".');

        (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);
    }

    public function testWorkoutGenerationRejectsInvalidGeneratedMovementItems(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $row = new Movement('Row', $difficulty, $cardio);

        $movementService = new class([$row]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "AMRAP 10 minutes\n250 m Row",
                    'scalingOptions' => "RX: as written\nIntermediate: 200 m Row\nScaled: 150 m Row",
                    'movements' => ['Row', 42],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Invalid generated movement item test')
            ->setTimeCap(10)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setNumberOfDifferentMovements(1)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OpenAI workout generation returned an invalid workout payload.');

        (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);
    }

    public function testWorkoutGenerationRejectsDuplicateGeneratedMovementNames(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $row = new Movement('Row', $difficulty, $cardio);

        $movementService = new class([$row]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "AMRAP 10 minutes\n250 m Row",
                    'scalingOptions' => "RX: as written\nIntermediate: 200 m Row\nScaled: 150 m Row",
                    'movements' => ['Row', ' row '],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Duplicate generated movement names test')
            ->setTimeCap(10)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setNumberOfDifferentMovements(1)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OpenAI workout generation returned duplicate movement "row".');

        (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);
    }

    public function testWorkoutGenerationRejectsAllowedMovementInFlowWhenNotListed(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $row = new Movement('Row', $difficulty, $cardio);
        $burpee = new Movement('Burpee', $difficulty, $cardio);

        $movementService = new class([$row, $burpee]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "AMRAP 10 minutes\n250 m Row\n10 Burpees",
                    'scalingOptions' => "RX: as written\nIntermediate: 200 m Row and 8 Burpees\nScaled: 150 m Row and 6 Burpees",
                    'movements' => ['Row'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Unlisted movement in generated flow test')
            ->setTimeCap(10)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setNumberOfDifferentMovements(1)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OpenAI workout generation included movement "Burpee" in the flow but did not list it in movements.');

        (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);
    }

    public function testWorkoutGenerationRejectsSelectedMovementMissingFromFlow(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $row = new Movement('Row', $difficulty, $cardio);

        $movementService = new class([$row]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "AMRAP 10 minutes\n250 m Ski",
                    'scalingOptions' => "RX: as written\nIntermediate: 200 m Ski\nScaled: 150 m Ski",
                    'movements' => ['Row'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Selected movement missing from flow test')
            ->setTimeCap(10)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setNumberOfDifferentMovements(1)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OpenAI workout generation listed movement "Row" but did not include it in the workout flow.');

        (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);
    }

    public function testWorkoutGenerationReconcilesListedMovementMissingFromFlowWhenFlowHasExpectedMovements(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $weightlifting = new MovementType(MovementTypeEnum::WEIGHTLIFTING);
        $gymnastics = new MovementType(MovementTypeEnum::GYMNASTIC);
        $plyometric = new MovementType(MovementTypeEnum::PLYOMETRIC);
        $row = new Movement('Row', $difficulty, $cardio);
        $thruster = new Movement('Thruster', $difficulty, $weightlifting);
        $pullUp = new Movement('Pull Up', $difficulty, $gymnastics);
        $boxJump = new Movement('Box Jump', $difficulty, $plyometric);
        $boxStepUp = new Movement('Box Step Up', $difficulty, $plyometric);

        $movementService = new class([$row, $thruster, $pullUp, $boxJump, $boxStepUp]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "AMRAP 16 minutes\n- 250 m Row\n- 12 Thruster\n- 9 Pull Up\n- 12 Box Step Up",
                    'scalingOptions' => "RX: as written\nIntermediate: reduce load\nScaled: reduce volume",
                    'movements' => ['Row', 'Thruster', 'Pull Up', 'Box Jump'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Reconcile listed movement missing from flow test')
            ->setTimeCap(16)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio, $weightlifting, $gymnastics, $plyometric])
            ->setNumberOfDifferentMovements(4)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        self::assertSame(['Row', 'Thruster', 'Pull Up', 'Box Step Up'], array_map(
            static fn (Movement $movement): ?string => $movement->getName(),
            $workout->getMovements()->toArray()
        ));
    }

    public function testWorkoutGenerationReconcilesListedShortMovementWithLongerMovementInFlow(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $gymnastics = new MovementType(MovementTypeEnum::GYMNASTIC);
        $pushUp = new Movement('Push Up', $difficulty, $gymnastics);
        $handstandPushUp = new Movement('Handstand Push Up', $difficulty, $gymnastics);

        $movementService = new class([$pushUp, $handstandPushUp]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "AMRAP 10 minutes\n10 Handstand Push Ups",
                    'scalingOptions' => "RX: as written\nIntermediate: 8 Handstand Push Ups\nScaled: 10 Pike Push Ups",
                    'movements' => ['Push Up'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Short movement reconciled with longer movement in flow test')
            ->setTimeCap(10)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$gymnastics])
            ->setNumberOfDifferentMovements(1)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        self::assertSame(['Handstand Push Up'], array_map(
            static fn (Movement $movement): ?string => $movement->getName(),
            $workout->getMovements()->toArray()
        ));
    }

    public function testWorkoutGenerationAllowsLongerMovementWhenShorterMovementIsBanned(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $gymnastics = new MovementType(MovementTypeEnum::GYMNASTIC);
        $pushUp = new Movement('Push Up', $difficulty, $gymnastics);
        $handstandPushUp = new Movement('Handstand Push Up', $difficulty, $gymnastics);

        $movementService = new class([$handstandPushUp]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "AMRAP 10 minutes\n10 Handstand Push Ups",
                    'scalingOptions' => "RX: as written\nIntermediate: 8 Handstand Push Ups\nScaled: 10 Pike Push Ups",
                    'movements' => ['Handstand Push Up'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Longer movement with shorter banned movement test')
            ->setTimeCap(10)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$gymnastics])
            ->setBannedMovements([$pushUp])
            ->setNumberOfDifferentMovements(1)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        self::assertSame(['Handstand Push Up'], array_map(
            static fn (Movement $movement): ?string => $movement->getName(),
            $workout->getMovements()->toArray()
        ));
    }

    public function testWorkoutGenerationAcceptsEchoBikeInFlowForAssaultBikeCatalogMovement(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $assaultBike = new Movement('Assault Bike', $difficulty, $cardio);

        $movementService = new class([$assaultBike]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public string $prompt = '';

            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                $this->prompt = $prompt;

                return json_encode([
                    'flow' => "AMRAP 12 minutes\n10 Echo Bike calories",
                    'scalingOptions' => "RX: as written\nIntermediate: 8 calories\nScaled: 6 calories",
                    'movements' => ['Assault Bike'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Echo Bike display for Assault Bike movement test')
            ->setTimeCap(12)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setNumberOfDifferentMovements(1)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        self::assertStringContainsString('write Echo Bike in the athlete-facing flow', $chatGpt->prompt);
        self::assertStringContainsString('10 Echo Bike calories', $workout->getFlow());
        self::assertSame(['Assault Bike'], array_map(
            static fn (Movement $movement): ?string => $movement->getName(),
            $workout->getMovements()->toArray()
        ));
    }

    public function testWorkoutGenerationRecognizesChestToBarAbbreviationInFlow(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $gymnastics = new MovementType(MovementTypeEnum::GYMNASTIC);
        $pullUp = new Movement('Pull Up', $difficulty, $gymnastics);
        $chestToBarPullUp = new Movement('Chest to Bar Pull Up', $difficulty, $gymnastics);

        $movementService = new class([$pullUp, $chestToBarPullUp]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "AMRAP 10 minutes\n10 C2B pull-ups",
                    'scalingOptions' => "RX: as written\nIntermediate: 8 C2B pull-ups\nScaled: 10 jumping pull-ups",
                    'movements' => ['Chest to Bar Pull Up'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Chest to bar alias test')
            ->setTimeCap(10)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$gymnastics])
            ->setNumberOfDifferentMovements(1)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        self::assertSame(['Chest to Bar Pull Up'], array_map(
            static fn (Movement $movement): ?string => $movement->getName(),
            $workout->getMovements()->toArray()
        ));
    }

    public function testWorkoutGenerationRecognizesCommonMovementAbbreviationsInFlow(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $gymnastics = new MovementType(MovementTypeEnum::GYMNASTIC);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $pushUp = new Movement('Push Up', $difficulty, $gymnastics);
        $handstandPushUp = new Movement('Handstand Push Up', $difficulty, $gymnastics);
        $toesToBar = new Movement('Toes to Bar', $difficulty, $gymnastics);
        $doubleUnder = new Movement('Double Under', $difficulty, $cardio);

        $movementService = new class([$pushUp, $handstandPushUp, $toesToBar, $doubleUnder]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "AMRAP 12 minutes\n8 HSPU\n12 T2B\n40 DU",
                    'scalingOptions' => "RX: as written\nIntermediate: 6 HSPU, 10 T2B, 30 DU\nScaled: pike push-ups, knee raises, single-unders",
                    'movements' => ['Handstand Push Up', 'Toes to Bar', 'Double Under'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Common movement aliases test')
            ->setTimeCap(12)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$gymnastics, $cardio])
            ->setNumberOfDifferentMovements(3)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        self::assertSame(['Handstand Push Up', 'Toes to Bar', 'Double Under'], array_map(
            static fn (Movement $movement): ?string => $movement->getName(),
            $workout->getMovements()->toArray()
        ));
    }

    public function testWorkoutGenerationDoesNotMatchShortAliasesInsideLongerWords(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $weightlifting = new MovementType(MovementTypeEnum::WEIGHTLIFTING);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $clean = new Movement('Clean', $difficulty, $weightlifting);
        $doubleUnder = new Movement('Double Under', $difficulty, $cardio);

        $movementService = new class([$clean, $doubleUnder]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "AMRAP 10 minutes\n10 dumbbell cleans",
                    'scalingOptions' => "RX: as written\nIntermediate: 8 dumbbell cleans\nScaled: lighter dumbbell cleans",
                    'movements' => ['Clean'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Short movement alias false positive test')
            ->setTimeCap(10)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$weightlifting, $cardio])
            ->setNumberOfDifferentMovements(1)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        self::assertSame(['Clean'], array_map(
            static fn (Movement $movement): ?string => $movement->getName(),
            $workout->getMovements()->toArray()
        ));
    }

    public function testWorkoutGenerationRecognizesCommonCrossfitMovementShorthand(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $gymnastics = new MovementType(MovementTypeEnum::GYMNASTIC);
        $weightlifting = new MovementType(MovementTypeEnum::WEIGHTLIFTING);
        $chestToBarPullUp = new Movement('Chest to Bar Pull Up', $difficulty, $gymnastics);
        $muscleUp = new Movement('Muscle Up', $difficulty, $gymnastics);
        $wallBallShot = new Movement('Wall Ball Shot', $difficulty, $weightlifting);

        $movementService = new class([$chestToBarPullUp, $muscleUp, $wallBallShot]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "AMRAP 15 minutes\n10 C2B\n5 BMU\n20 wall balls",
                    'scalingOptions' => "RX: as written\nIntermediate: 8 C2B, 3 RMU, 16 wall balls\nScaled: jumping pull-ups, transitions and light wall balls",
                    'movements' => ['Chest to Bar Pull Up', 'Muscle Up', 'Wall Ball Shot'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Common CrossFit shorthand test')
            ->setTimeCap(15)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$gymnastics, $weightlifting])
            ->setNumberOfDifferentMovements(3)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        self::assertSame(['Chest to Bar Pull Up', 'Muscle Up', 'Wall Ball Shot'], array_map(
            static fn (Movement $movement): ?string => $movement->getName(),
            $workout->getMovements()->toArray()
        ));
    }

    public function testWorkoutGenerationResolvesMovementAliasesReturnedByOpenAI(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $gymnastics = new MovementType(MovementTypeEnum::GYMNASTIC);
        $weightlifting = new MovementType(MovementTypeEnum::WEIGHTLIFTING);
        $chestToBarPullUp = new Movement('Chest to Bar Pull Up', $difficulty, $gymnastics);
        $muscleUp = new Movement('Muscle Up', $difficulty, $gymnastics);
        $wallBallShot = new Movement('Wall Ball Shot', $difficulty, $weightlifting);

        $movementService = new class([$chestToBarPullUp, $muscleUp, $wallBallShot]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "AMRAP 15 minutes\n10 C2B\n5 BMU\n20 wall balls",
                    'scalingOptions' => "RX: as written\nIntermediate: 8 C2B, 3 BMU, 16 wall balls\nScaled: jumping pull-ups, transitions and light wall balls",
                    'movements' => ['C2B', 'BMU', 'Wall Balls'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Generated movement aliases test')
            ->setTimeCap(15)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$gymnastics, $weightlifting])
            ->setNumberOfDifferentMovements(3)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        self::assertSame(['Chest to Bar Pull Up', 'Muscle Up', 'Wall Ball Shot'], array_map(
            static fn (Movement $movement): ?string => $movement->getName(),
            $workout->getMovements()->toArray()
        ));
    }

    public function testWorkoutGenerationRejectsDuplicateMovementAliasesReturnedByOpenAI(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $gymnastics = new MovementType(MovementTypeEnum::GYMNASTIC);
        $muscleUp = new Movement('Muscle Up', $difficulty, $gymnastics);

        $movementService = new class([$muscleUp]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "AMRAP 10 minutes\n5 BMU",
                    'scalingOptions' => "RX: as written\nIntermediate: 3 BMU\nScaled: transitions",
                    'movements' => ['Muscle Up', 'BMU'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Duplicate generated movement aliases test')
            ->setTimeCap(10)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$gymnastics])
            ->setNumberOfDifferentMovements(1)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OpenAI workout generation returned duplicate movement "BMU".');

        (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);
    }

    public function testWorkoutGenerationResolvesCardioMachineAliasesReturnedByOpenAI(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $row = new Movement('Row', $difficulty, $cardio);
        $skiErg = new Movement('Ski Erg', $difficulty, $cardio);
        $bikeErg = new Movement('Bike Erg', $difficulty, $cardio);

        $movementService = new class([$row, $skiErg, $bikeErg]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "AMRAP 18 minutes\n1 minute rowing for calories\n15 ski calories\n20 BikeErg calories",
                    'scalingOptions' => "RX: as written\nIntermediate: reduce machine calories\nScaled: shorten each machine effort",
                    'movements' => ['Rowing', 'Ski', 'BikeErg'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Generated cardio machine aliases test')
            ->setTimeCap(18)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setNumberOfDifferentMovements(3)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        self::assertSame(['Row', 'Ski Erg', 'Bike Erg'], array_map(
            static fn (Movement $movement): ?string => $movement->getName(),
            $workout->getMovements()->toArray()
        ));
    }

    public function testWorkoutGenerationResolvesWeightliftingShorthandReturnedByOpenAI(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $weightlifting = new MovementType(MovementTypeEnum::WEIGHTLIFTING);
        $shoulderToOverhead = new Movement('Shoulder To Overhead', $difficulty, $weightlifting);
        $cleanAndJerk = new Movement('Clean and Jerk', $difficulty, $weightlifting);
        $overheadSquat = new Movement('Overhead Squat', $difficulty, $weightlifting);

        $movementService = new class([$shoulderToOverhead, $cleanAndJerk, $overheadSquat]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "For time:\n21 S2OH\n15 C&J\n9 OHS",
                    'scalingOptions' => "RX: as written\nIntermediate: 15 STOH, 12 Clean & Jerk, 9 overhead squats\nScaled: reduce load",
                    'movements' => ['S2OH', 'C&J', 'OHS'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Generated weightlifting shorthand test')
            ->setTimeCap(12)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::FOR_TIME))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$weightlifting])
            ->setNumberOfDifferentMovements(3)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        self::assertSame(['Shoulder To Overhead', 'Clean and Jerk', 'Overhead Squat'], array_map(
            static fn (Movement $movement): ?string => $movement->getName(),
            $workout->getMovements()->toArray()
        ));
    }

    public function testWorkoutGenerationResolvesBodyweightMovementAliasesReturnedByOpenAI(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $gymnastics = new MovementType(MovementTypeEnum::GYMNASTIC);
        $plyometric = new MovementType(MovementTypeEnum::PLYOMETRIC);
        $burpeeBoxJumpOver = new Movement('Burpee Box Jump Over', $difficulty, $plyometric);
        $handstandWalk = new Movement('Handstand Walk', $difficulty, $gymnastics);
        $wallWalk = new Movement('Wall Walk', $difficulty, $gymnastics);
        $boxStepUp = new Movement('Box Step Up', $difficulty, $plyometric);

        $movementService = new class([$burpeeBoxJumpOver, $handstandWalk, $wallWalk, $boxStepUp]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "AMRAP 16 minutes\n12 BBJO\n25-ft HSW\n8 wall walks\n20 box step-ups",
                    'scalingOptions' => "RX: as written\nIntermediate: 10 burpee box jump-overs, shorter HS walk, 6 wall walks, 16 box step-ups\nScaled: step-over, bear crawl, wall walk scale, lower box",
                    'movements' => ['BBJO', 'HSW', 'Wall Walks', 'Box Step-Ups'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Generated bodyweight aliases test')
            ->setTimeCap(16)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$gymnastics, $plyometric])
            ->setNumberOfDifferentMovements(4)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        self::assertSame(['Burpee Box Jump Over', 'Handstand Walk', 'Wall Walk', 'Box Step Up'], array_map(
            static fn (Movement $movement): ?string => $movement->getName(),
            $workout->getMovements()->toArray()
        ));
    }

    public function testWorkoutGenerationResolvesAccessoryMovementAliasesReturnedByOpenAI(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $gymnastics = new MovementType(MovementTypeEnum::GYMNASTIC);
        $strongman = new MovementType(MovementTypeEnum::STRONGMAN);
        $pistolSquat = new Movement('Pistol Squat', $difficulty, $gymnastics);
        $ghdSitUp = new Movement('GHD Sit Up', $difficulty, $gymnastics);
        $farmerCarry = new Movement('Farmer Carry', $difficulty, $strongman);
        $sledPush = new Movement('Sled Push', $difficulty, $strongman);

        $movementService = new class([$pistolSquat, $ghdSitUp, $farmerCarry, $sledPush]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "4 rounds for time:\n20 pistols\n30 GHD sit-ups\n100-ft farmer's carry\n50-ft sled pushes",
                    'scalingOptions' => "RX: as written\nIntermediate: assisted pistols, reduced GHD sit-ups, lighter farmers carry and sled-pushes\nScaled: air squats, abmat sit-ups, light carry and sled work",
                    'movements' => ['Pistols', 'GHD Sit-Ups', 'Farmer\'s Carry', 'Sled Pushes'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Generated accessory aliases test')
            ->setTimeCap(18)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::FOR_TIME))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$gymnastics, $strongman])
            ->setNumberOfDifferentMovements(4)
            ->setNumberOfRounds(4)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        self::assertSame(['Pistol Squat', 'GHD Sit Up', 'Farmer Carry', 'Sled Push'], array_map(
            static fn (Movement $movement): ?string => $movement->getName(),
            $workout->getMovements()->toArray()
        ));
    }

    public function testWorkoutGenerationResolvesPluralMovementNamesReturnedByOpenAI(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $plyometric = new MovementType(MovementTypeEnum::PLYOMETRIC);
        $weightlifting = new MovementType(MovementTypeEnum::WEIGHTLIFTING);
        $run = new Movement('Run', $difficulty, $cardio);
        $burpee = new Movement('Burpee', $difficulty, $cardio);
        $boxJump = new Movement('Box Jump', $difficulty, $plyometric);
        $clean = new Movement('Clean', $difficulty, $weightlifting);

        $movementService = new class([$run, $burpee, $boxJump, $clean]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "For time:\n400 m runs\n30 burpees\n20 box jumps\n10 cleans",
                    'scalingOptions' => "RX: as written\nIntermediate: shorter runs, 20 burpees, 15 box jumps, 8 cleans\nScaled: shorter distance, fewer reps and lighter load",
                    'movements' => ['Runs', 'Burpees', 'Box Jumps', 'Cleans'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Generated plural movement names test')
            ->setTimeCap(14)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::FOR_TIME))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio, $plyometric, $weightlifting])
            ->setNumberOfDifferentMovements(4)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        self::assertSame(['Run', 'Burpee', 'Box Jump', 'Clean'], array_map(
            static fn (Movement $movement): ?string => $movement->getName(),
            $workout->getMovements()->toArray()
        ));
    }

    public function testWorkoutGenerationRejectsIncompleteGeneratedMovementList(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $row = new Movement('Row', $difficulty, $cardio);
        $burpee = new Movement('Burpee', $difficulty, $cardio);

        $movementService = new class([$row, $burpee]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "AMRAP 10 minutes\n250 m Row",
                    'scalingOptions' => "RX: as written\nIntermediate: 200 m Row\nScaled: 150 m Row",
                    'movements' => ['Row'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Incomplete generated movement list test')
            ->setTimeCap(10)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setNumberOfDifferentMovements(2)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OpenAI workout generation returned 1 allowed movement, expected 2.');

        (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);
    }

    public function testWorkoutGenerationRejectsExtraGeneratedMovementList(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $row = new Movement('Row', $difficulty, $cardio);
        $burpee = new Movement('Burpee', $difficulty, $cardio);

        $movementService = new class([$row, $burpee]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "AMRAP 10 minutes\n250 m Row\n10 Burpees",
                    'scalingOptions' => "RX: as written\nIntermediate: 200 m Row and 8 Burpees\nScaled: 150 m Row and 6 Burpees",
                    'movements' => ['Row', 'Burpee'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Extra generated movement list test')
            ->setTimeCap(10)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setNumberOfDifferentMovements(1)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OpenAI workout generation returned 2 allowed movements, expected 1.');

        (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);
    }

    public function testWorkoutGenerationRejectsMandatoryMovementMissingFromFlow(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $row = new Movement('Row', $difficulty, $cardio);
        $burpee = new Movement('Burpee', $difficulty, $cardio);

        $movementService = new class([$burpee]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "AMRAP 10 minutes\n10 Burpees",
                    'scalingOptions' => "RX: as written\nIntermediate: 8 Burpees\nScaled: 6 Burpees",
                    'movements' => ['Burpee'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Mandatory movement missing from flow test')
            ->setTimeCap(10)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setMandatoryMovements([$row])
            ->setNumberOfDifferentMovements(2)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OpenAI workout generation did not include mandatory movement "Row" in the workout flow.');

        (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);
    }

    public function testWorkoutGenerationRejectsBannedMovementInFlow(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $row = new Movement('Row', $difficulty, $cardio);
        $burpee = new Movement('Burpee', $difficulty, $cardio);

        $movementService = new class([$row]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "AMRAP 10 minutes\n250 m Row\n10 Burpees",
                    'scalingOptions' => "RX: as written\nIntermediate: 200 m Row\nScaled: 150 m Row",
                    'movements' => ['Row'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Banned movement in flow test')
            ->setTimeCap(10)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setBannedMovements([$burpee])
            ->setNumberOfDifferentMovements(1)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('OpenAI workout generation included banned movement "Burpee" in the workout flow.');

        (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);
    }

    public function testWorkoutGenerationAcceptsStructuredScalingOptions(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $row = new Movement('Row', $difficulty, $cardio);

        $movementService = new class([$row]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                return json_encode([
                    'flow' => "For time:\n1000 m Row",
                    'scalingOptions' => [
                        ['level' => 'RX', 'description' => 'as written'],
                        ['level' => 'Intermediate', 'description' => '800 m Row'],
                        ['level' => 'Scaled', 'description' => '600 m Row'],
                    ],
                    'movements' => ['Row'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Structured scaling options test')
            ->setTimeCap(12)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::FOR_TIME))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setNumberOfDifferentMovements(1)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        self::assertStringContainsString("Scaling options:\nRX: as written", $workout->getFlow());
        self::assertStringContainsString('Intermediate: 800 m Row', $workout->getFlow());
        self::assertStringContainsString('Scaled: 600 m Row', $workout->getFlow());
        self::assertStringNotContainsString('Array', $workout->getFlow());
    }

    public function testPrescriptionStandardPromptBuilderAddsRelevantHyroxAndCrossfitLoads(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::RX);
        $weightlifting = new MovementType(MovementTypeEnum::WEIGHTLIFTING);
        $deadlift = new Movement('Deadlift', $difficulty, $weightlifting);

        $repository = $this->createMock(WorkoutPrescriptionStandardRepository::class);
        $repository
            ->expects(self::once())
            ->method('findForPrompt')
            ->with('RX', ['Deadlift'], [], true)
            ->willReturn([
                new WorkoutPrescriptionStandard('hyrox_official_25_26', 'hyrox', 'RX', 'men', 'Sled Push', 'sled', '152.00', 'kg', 1, 'Open men / mixed', 'Includes sled', 10),
                new WorkoutPrescriptionStandard('crossfit_common', 'crossfit', 'RX', 'women', 'Deadlift', 'barbell', '70.00', 'kg', 1, '155 lb-style RX deadlift', null, 30),
            ]);

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Hyrox strength')
            ->setStimulus('Wod axé Hyrox')
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::FOR_TIME))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setNumberOfDifferentMovements(1)
            ->setIsTeamWorkout(false);

        $prompt = (new WorkoutPrescriptionStandardPromptBuilder($repository))->build($workoutGeneration, [$deadlift]);

        self::assertStringContainsString('Known load prescription standards', $prompt);
        self::assertStringContainsString('hyrox / RX / hyrox_official_25_26', $prompt);
        self::assertStringContainsString('Men Sled Push: 152 kg (Open men / mixed) - Includes sled', $prompt);
        self::assertStringContainsString('Women Deadlift: 70 kg (155 lb-style RX deadlift)', $prompt);
        self::assertStringContainsString('Use exact movement standards before generic implement standards', $prompt);
        self::assertStringContainsString('Use these as anchors', $prompt);
    }

    public function testPrescriptionStandardPromptBuilderGroupsObservedLoadProgressions(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::RX);
        $weightlifting = new MovementType(MovementTypeEnum::WEIGHTLIFTING);
        $deadlift = new Movement('Deadlift', $difficulty, $weightlifting);

        $repository = $this->createMock(WorkoutPrescriptionStandardRepository::class);
        $repository
            ->expects(self::once())
            ->method('findForPrompt')
            ->with('RX', ['Deadlift'], [], false)
            ->willReturn([
                new WorkoutPrescriptionStandard('crossfit_games_observed', 'crossfit', 'RX', 'women', 'Deadlift', 'barbell', '102.00', 'kg', 1, null, 'Age-Group Quarterfinals Workout 3 | 155/185/225 lb ~= 70/83/102 kg barbell conversion', 80),
                new WorkoutPrescriptionStandard('crossfit_games_observed', 'crossfit', 'RX', 'women', 'Deadlift', 'barbell', '70.00', 'kg', 1, null, 'Age-Group Quarterfinals Workout 3 | 155/185/225 lb ~= 70/83/102 kg barbell conversion', 80),
                new WorkoutPrescriptionStandard('crossfit_games_observed', 'crossfit', 'RX', 'women', 'Deadlift', 'barbell', '83.00', 'kg', 1, null, 'Age-Group Quarterfinals Workout 3 | 155/185/225 lb ~= 70/83/102 kg barbell conversion', 80),
            ]);

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Deadlift ladder')
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::FOR_TIME))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setNumberOfDifferentMovements(1)
            ->setIsTeamWorkout(false);

        $prompt = (new WorkoutPrescriptionStandardPromptBuilder($repository))->build($workoutGeneration, [$deadlift]);

        self::assertStringContainsString('Women Deadlift progression: 70 kg / 83 kg / 102 kg', $prompt);
        self::assertStringNotContainsString('Women Deadlift: 70 kg', $prompt);
        self::assertStringNotContainsString('Women Deadlift: 83 kg', $prompt);
        self::assertStringNotContainsString('Women Deadlift: 102 kg', $prompt);
    }

    /**
     * @return array{workout: \App\Entity\Workout\Workout, prompt: string}
     */
    private function createWorkoutAndCapturePrompt(
        WorkoutTypeEnum $workoutType,
        ?int $numberOfRounds,
        ?string $stimulus = null,
        int $numberOfDifferentMovements = 1,
    ): array {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $row = new Movement('Row', $difficulty, $cardio);
        $burpee = new Movement('Burpee', $difficulty, $cardio);

        $movementService = new class([$row, $burpee]) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };

        $chatGpt = new class implements ChatGPTApiKeyInterface {
            public string $prompt = '';

            public function getWorkoutFlowFromPrompt(string $prompt): string
            {
                $this->prompt = $prompt;
                $isTwoMovementWorkout = str_contains($prompt, 'Choose exactly 2 different movements');

                return json_encode([
                    'flow' => $isTwoMovementWorkout ? "Workout:\n1000 m Row\n20 Burpee" : "Workout:\n1000 m Row",
                    'scalingOptions' => "RX: as written\nIntermediate: 800 m Row\nScaled: 600 m Row",
                    'movements' => $isTwoMovementWorkout ? ['Row', 'Burpee'] : ['Row'],
                ], JSON_THROW_ON_ERROR);
            }
        };

        $workoutOriginService = new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Optional rounds prompt test')
            ->setStimulus($stimulus)
            ->setTimeCap(12)
            ->setWorkoutType(new WorkoutType($workoutType))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes([$cardio])
            ->setNumberOfDifferentMovements($numberOfDifferentMovements)
            ->setNumberOfRounds($numberOfRounds)
            ->setIntervalsTime(90)
            ->setIntervalsRestTime(30)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        return [
            'workout' => $workout,
            'prompt' => $chatGpt->prompt,
        ];
    }

    /**
     * @param list<MovementType> $movementTypes
     */
    private function competitionWorkoutGeneration(MovementDifficulty $difficulty, array $movementTypes): WorkoutGeneration
    {
        return (new WorkoutGeneration())
            ->setName('Competition slate test')
            ->setStimulus('Competition')
            ->setStimulusIntent('Tester plusieurs qualités simultanément.')
            ->setTimeCap(15)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::FOR_TIME))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($difficulty)
            ->setMovementTypes($movementTypes)
            ->setNumberOfDifferentMovements(3)
            ->setNumberOfRounds(3)
            ->setIsTeamWorkout(false);
    }

    /**
     * @param list<Movement> $candidateMovements
     *
     * @return list<Movement>
     */
    private function candidateMovementsForPrompt(WorkoutCreatorService $service, WorkoutGeneration $workoutGeneration, array $candidateMovements): array
    {
        $method = new \ReflectionMethod($service, 'candidateMovementsForPrompt');
        $method->setAccessible(true);

        return $method->invoke($service, $workoutGeneration, $candidateMovements);
    }

    /**
     * @param list<Movement> $possibleMovements
     */
    private function movementServiceReturning(array $possibleMovements): MovementServiceInterface
    {
        return new class($possibleMovements) implements MovementServiceInterface {
            /**
             * @param list<Movement> $possibleMovements
             */
            public function __construct(private readonly array $possibleMovements)
            {
            }

            public function getWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleWorkoutMovementsFromWorkoutGeneration(WorkoutGeneration $workoutGeneration): array
            {
                return $this->possibleMovements;
            }

            public function removeNotAvailableImplementsFromMovementsOfWorkout(Collection $possibleImplements, array $movements): array
            {
                return $movements;
            }

            public function getMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getPossibleMovementsFromMuscles(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getWorkoutMovementsFromPossibleMovements(array $possibleMovements, WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        };
    }

    private function workoutOriginService(): WorkoutOriginServiceInterface
    {
        return new class implements WorkoutOriginServiceInterface {
            public function getExistingOrInsertNewWorkoutOrigin(string $name, int $year): WorkoutOrigin
            {
                return new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), $year);
            }
        };
    }
}
