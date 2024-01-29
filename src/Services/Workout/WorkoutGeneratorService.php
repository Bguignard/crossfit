<?php

namespace App\Services\Workout;

use App\Entity\Workout\Block;
use App\Entity\Workout\Enum\MovementTypeEnum;
use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use App\Entity\Workout\Enum\WorkoutTypeEnum;
use App\Entity\Workout\MovementCluster;
use App\Entity\Workout\Workout;

final readonly class WorkoutGeneratorService implements WorkoutGeneratorServiceInterface
{
    public function __construct(
        private MovementClusterGeneratorService $movementClusterGeneratorService,
        private int $maxNumberOfRounds,
        private WorkoutOriginServiceInterface $workoutOriginService,
    ) {
    }

    /**
     * How to generate a workout:
     * Criteria :
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
        ?string $name,
        array $workoutMovementTypes,
        ?WorkoutTypeEnum $workoutType,
        int $numberOfDifferentMovements,
        int $workoutTimeCap,
        int $cardioIntensity,
        int $gymnasticIntensity,
        int $weightliftingIntensity,
        int $weightIntensity,
        bool $intervals,
        ?int $intervalsTime,
        ?int $intervalsRestTime,
        ?array $mandatoryMovements,
        ?array $mandatoryImplements,
        ?int $maxDifficulty
    ): Workout {
        $name = $name ?? $this->generateWorkoutName();
        $workoutType = $workoutType ?? $this->setRandWorkoutType();
        if (WorkoutTypeEnum::FOR_TIME === $workoutType) {
            $workoutTimeCap = $workoutTimeCap ?? rand(5, 60);
        }
        $workoutOrigin = $this->workoutOriginService->insertNewWorkoutOrigin(WorkoutOriginNameEnum::CUSTOM->value, intval(date('Y')));

        $workout = new Workout(
            $name,
            $this->getNumberOfForTimeWorkoutRounds($numberOfDifferentMovements),
            $workoutTimeCap,
            $workoutType,
            $workoutOrigin,
            $this->generateBlocks(
                $numberOfDifferentMovements,
                $mandatoryMovements,
                $maxDifficulty,
                $mandatoryImplements,
            ),
        );

        // todo : set reps

        return $workout;
    }

    private function generateWorkoutName(): string
    {
        return 'Custom workout';
    }

    /**  @return Block[] */
    private function generateBlocks(
        int $numberOfDifferentMovements,
        ?array $mandatoryMovements,
        ?int $maxDifficulty,
        ?array $availableImplements,
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
            ),
            null,
        );

        return $blocks;
    }

    /**  @return MovementCluster[] */
    private function generateWorkoutMovementsClusters(
        int $numberOfDifferentMovements,
        ?array $mandatoryMovements,
        ?int $maxDifficulty,
        ?array $availableImplements,
    ): array {
        $numberOfGeneratedMovements = 0;
        $movementClusters = [];

        // todo : for each movement cluster, determine the movement type  (gym, cardio, wl) to generate it

        while ($numberOfGeneratedMovements < $numberOfDifferentMovements) {
            $movementClusters[] = $this->movementClusterGeneratorService->generateMovementCluster(
                $availableImplements,
                $maxDifficulty,
                $mandatoryMovements,
                $this->getTypeOfMovements(),
            );
            ++$numberOfGeneratedMovements;
        }

        return $movementClusters;
    }

    private function setRandWorkoutType(): WorkoutTypeEnum
    {
        return WorkoutTypeEnum::cases()[rand(0, count(WorkoutTypeEnum::cases()) - 1)];
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
