<?php

namespace App\Tests;

use App\Entity\Workout\Enum\MovementDifficultyEnum;
use App\Entity\Workout\Enum\MovementTypeEnum;
use App\Entity\Workout\Enum\WorkoutMovementGenerationTypeEnum;
use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use App\Entity\Workout\Enum\WorkoutTypeEnum;
use App\Entity\Workout\Movement;
use App\Entity\Workout\MovementDifficulty;
use App\Entity\Workout\MovementType;
use App\Entity\Workout\WorkoutMovementGenerationType;
use App\Entity\Workout\WorkoutOrigin;
use App\Entity\Workout\WorkoutOriginName;
use App\Entity\Workout\WorkoutPrescriptionStandard;
use App\Entity\Workout\WorkoutType;
use App\Entity\WorkoutGeneration\WorkoutGeneration;
use App\Repository\Workout\WorkoutPrescriptionStandardRepository;
use App\Services\Workout\ChatGPTApiKeyInterface;
use App\Services\Workout\MovementServiceInterface;
use App\Services\Workout\WorkoutCreatorService;
use App\Services\Workout\WorkoutOriginServiceInterface;
use App\Services\Workout\WorkoutPrescriptionStandardPromptBuilder;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class WorkoutCreatorServiceTest extends TestCase
{
    public function testOpenAiChoosesMovementsFromTheCompletePossiblePool(): void
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $cardio = new MovementType(MovementTypeEnum::CARDIO);
        $run = new Movement('Run', $difficulty, $cardio);
        $row = new Movement('Row', $difficulty, $cardio);
        $burpee = new Movement('Burpee', $difficulty, $cardio);

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
            ->setNumberOfDifferentMovements(2)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $workout = (new WorkoutCreatorService($movementService, $chatGpt, $workoutOriginService))->createWorkout($workoutGeneration);

        self::assertStringContainsString('Candidate movement pool', $chatGpt->prompt);
        self::assertStringContainsString('- Run', $chatGpt->prompt);
        self::assertStringContainsString('- Row', $chatGpt->prompt);
        self::assertStringContainsString('- Burpee', $chatGpt->prompt);
        self::assertStringContainsString('Level prescription guidance: create an Intermediate version', $chatGpt->prompt);
        self::assertStringContainsString('always include level-appropriate male/female loads in kg', $chatGpt->prompt);
        self::assertStringContainsString('83 kg men / 61 kg women', $chatGpt->prompt);
        self::assertStringNotContainsString('185/135 lb', $chatGpt->prompt);
        self::assertStringContainsString('Scaling options', $chatGpt->prompt);
        self::assertStringContainsString('"scalingOptions"', $chatGpt->prompt);
        self::assertStringContainsString('Team workout guidance: this is an individual workout', $chatGpt->prompt);
        self::assertStringContainsString("Scaling options:\nRX: as written", $workout->getFlow());
        self::assertSame(['Run', 'Burpee'], array_map(
            static fn (Movement $movement): ?string => $movement->getName(),
            $workout->getMovements()->toArray()
        ));
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
        self::assertStringContainsString('you go, I go', $chatGpt->prompt);
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
}
