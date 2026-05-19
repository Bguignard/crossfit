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
use App\Entity\Workout\WorkoutType;
use App\Entity\WorkoutGeneration\WorkoutGeneration;
use App\Services\Workout\ChatGPTApiKeyInterface;
use App\Services\Workout\MovementServiceInterface;
use App\Services\Workout\WorkoutCreatorService;
use App\Services\Workout\WorkoutOriginServiceInterface;
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
        self::assertStringContainsString('Scaling options', $chatGpt->prompt);
        self::assertSame(['Run', 'Burpee'], array_map(
            static fn (Movement $movement): ?string => $movement->getName(),
            $workout->getMovements()->toArray()
        ));
    }
}
