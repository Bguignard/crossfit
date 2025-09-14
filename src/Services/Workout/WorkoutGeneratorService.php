<?php

namespace App\Services\Workout;

use App\Entity\Workout\Block;
use App\Entity\Workout\Enum\MovementTypeEnum;
use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use App\Entity\Workout\Enum\WorkoutTypeEnum;
use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\MovementCluster;
use App\Entity\Workout\Workout;
use App\Entity\Workout\WorkoutType;
use App\Repository\Workout\WorkoutTypeRepository;

final readonly class WorkoutGeneratorService implements WorkoutGeneratorServiceInterface
{
    public function __construct(
        private MovementClusterGeneratorService $movementClusterGeneratorService,
        private WorkoutOriginServiceInterface $workoutOriginService,
        private MovementGeneratorServiceInterface $movementGeneratorService,
    ) {
    }

    /**
     * How to generate a workout:
     * Criteria :
     * - Workout name
     * - Workout movement types selection (gyms, cardio, etc.) and dominance (50% gyms, 50% cardio)
     * - Workout type (AMRAP, EMOM, for time, etc.)
     * - Workout time cap
     * - Cardio intensity (Number of cals per minute) *If cardio is selected
     * - Gymnastic movements intensity (Number of reps per minute) *If gyms is selected
     * - Weightlifting movements intensity (Number of reps per minute) *If weightlifting is selected
     * - Weight intensity (%)
     * - Intervals ?
     * - Intervals time ?
     * - Intervals rest time ?
     * - Mandatory movements ?
     * - Mandatory Implements ?
     * - Number of different movements
     * - Versions of workout with different numbers of reps or different types of movements
     * - Max Difficulty ?
     */
    public function generateWorkout(
        string $name,
        array $workoutMovementTypes,
        WorkoutType $workoutTypeEntity,
        int $numberOfDifferentMovements,
        int $workoutTimeCap,
        array $mandatoryMovements,
        array $bannedMovements,
        array $availableImplements,
        int $cardioIntensity,
        int $gymnasticIntensity,
        int $weightliftingIntensity,
        int $weightIntensity,
        int $difficultyOfMovements,
        ?int $intervalsTime,
        ?int $intervalsRestTime,
        ?int $maxNumberOfRounds = 10,
    ): Workout {

        $workoutOrigin = $this->workoutOriginService->insertNewWorkoutOrigin(WorkoutOriginNameEnum::CUSTOM->value, intval(date('Y')));

        // if intervals we need the time and the rest time
        if($workoutTypeEntity->getNameAsEnum() === WorkoutTypeEnum::INTERVALS) {
            if( $intervalsTime === null || $intervalsRestTime === null) {
                throw new \InvalidArgumentException('Intervals time and rest time must be provided for INTERVALS workout type.');
            }
        }

        $numberOfRounds = $this->getNumberOfForTimeWorkoutRounds($numberOfDifferentMovements);
        $timePerBlock = $workoutTimeCap / $numberOfRounds;

        $workout = new Workout(
            $name,
            $numberOfRounds,
            $workoutTimeCap,
            $workoutTypeEntity,
            $workoutOrigin,
            $this->generateBlocks(
                $numberOfDifferentMovements,
                $mandatoryMovements,
                $difficultyOfMovements,
                $availableImplements,
                $bannedMovements,
                $timePerBlock,
            ),
        );

        // todo : set reps

        return $workout;
    }

    /**
     * @param Movement[]|null  $mandatoryMovements
     * @param Implement[]|null $availableImplements
     *
     * @return Block[]
     */
    private function generateBlocks(
        int $numberOfDifferentMovements,
        ?array $mandatoryMovements,
        ?int $maxDifficulty,
        ?array $availableImplements,
        ?array $forbiddenMovements = null,
        ?int $maximumTimeAllowedInSeconds = null,
    ): array {
        $blocks = [];
        $order = 1;
        $blocks[] = new Block(
            1,
            $order,
            $this->generateWorkoutMovementsClusters(
                $numberOfDifferentMovements,
                $mandatoryMovements,
                $maxDifficulty,
                $availableImplements,
                $forbiddenMovements,
                $maximumTimeAllowedInSeconds,
            ),
            null,
        );

        return $blocks;
    }

    /**
     * @param Movement[]|null  $mandatoryMovements
     * @param Movement[]|null  $forbiddenMovements
     * @param Implement[]|null $availableImplements
     *
     * @return MovementCluster[]
     */
    private function generateWorkoutMovementsClusters(
        int $numberOfDifferentMovements,
        ?array $mandatoryMovements,
        ?int $maxDifficulty,
        ?array $availableImplements,
        ?array $forbiddenMovements = null,
        ?int $maximumTimeAllowedInSeconds = null,
    ): array {
        $numberOfGeneratedMovements = 0;
        $movementClusters = [];
        if ($maximumTimeAllowedInSeconds === null) {
            $maximumTimeAllowedInSeconds = rand(3, 15);
        }

        if ($mandatoryMovements !== null && count($mandatoryMovements) > 0) {
            shuffle($mandatoryMovements);
        } else {
            // todo : for each movement cluster, determine the movement type  (gym, cardio, wl) to generate it
            $mandatoryMovements = [];

            while (count($mandatoryMovements) < $numberOfDifferentMovements) {
                $mandatoryMovements[] = $this->movementGeneratorService->generateMovement(
                    $availableImplements,
                    $maxDifficulty,
                    $forbiddenMovements,
                    $this->getTypeOfMovements());
            }
        }

        while ($numberOfGeneratedMovements < $numberOfDifferentMovements) {
            $movement = array_pop($mandatoryMovements);
            $possibleImplements = $movement->getPossibleImplements();
            shuffle($possibleImplements);
            $implement = $possibleImplements[0];
            $implementMeasureUnit = $implement->getImplementTypeOfAdjustableMeasure()->getMeasureUnits()->first()->getNameAsEnum();

            $movementClusters[] = $this->movementClusterGeneratorService->generateMovementCluster(
                $movement,
                $movement->getMovementExecutionTimeForMeasureUnits()->first()->getMeasureUnit(),
                $maximumTimeAllowedInSeconds,
                $implement,
                $implementMeasureUnit,
                null, // todo : add the intensity value depending on the rx standard
            );
            ++$numberOfGeneratedMovements;
        }

        return $movementClusters;
    }

    /*
      * The fewer movements, the more rounds
    */
    private function getNumberOfForTimeWorkoutRounds(int $numberOfDifferentMovements): int
    {
        if ($numberOfDifferentMovements <= 3) {
            return rand(4, $this->maxNumberOfRounds);
        }
        if ($numberOfDifferentMovements <= 5) {
            return rand(3, 5);
        }
        if ($numberOfDifferentMovements <= 7) {
            return rand(1, 3);
        }

        return rand(1, 2);
    }

    private function getTypeOfMovements(): MovementTypeEnum
    {
        return MovementTypeEnum::cases()[rand(0, count(MovementTypeEnum::cases()) - 1)];
    }

    // todo: for each clusters, generate the number of reps

    // todo : set the weights of barbells depending on the rx standard or 1RM %

    // todo : are dumbells or kb weights overriden ?
}
