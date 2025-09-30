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
use App\Entity\WorkoutGeneration\WorkoutGeneration;
use App\Repository\Workout\MovementRepository;

final readonly class WorkoutGeneratorService implements WorkoutGeneratorServiceInterface
{
    public function __construct(
        private MovementClusterGeneratorService $movementClusterGeneratorService,
        private WorkoutOriginServiceInterface $workoutOriginService,
        private MovementGeneratorServiceInterface $movementGeneratorService,
        private MovementRepository $movementRepository,
        private MovementDifficultyService $movementDifficultyService,
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
    public function generateWorkout(WorkoutGeneration $workoutGeneration): Workout
    {
        if (count($workoutGeneration->getMandatoryMovements()) > $workoutGeneration->getNumberOfDifferentMovements()) {
            throw new \InvalidArgumentException('The number of mandatory movements cannot be greater than the number of different movements.');
        }
        $maximumNumberOfRounds = 10;

        $workoutOrigin = $this->workoutOriginService->insertNewWorkoutOrigin(WorkoutOriginNameEnum::CUSTOM->value, intval(date('Y')));
        $numberOfRounds = $workoutGeneration->getNumberOfRounds() ?? $this->getNumberOfForTimeWorkoutRounds($workoutGeneration->getNumberOfDifferentMovements(), $maximumNumberOfRounds);

        // Movements generation
        $movementsInWorkout = $workoutGeneration->getMandatoryMovements()->toArray();

        if (count($movementsInWorkout) < $workoutGeneration->getNumberOfDifferentMovements()) {
            while (count($workoutGeneration->getMandatoryMovements()) < $workoutGeneration->getNumberOfDifferentMovements()) {
                $potentialMovements = $this->movementRepository->getMovementsByMovementTypesAndDifficulty(
                    $workoutGeneration->getMovementTypes()->toArray(),
                    $this->movementDifficultyService->getWorkoutDifficultiesFromOne($workoutGeneration->getMovementDifficulty()),
                    $movementsInWorkout,
                );

                dump($potentialMovements);

                $movementsInWorkout[] = $potentialMovements[array_rand($potentialMovements)];
            }
        }

        if ($workoutGeneration->getWorkoutType()->getNameAsEnum() === WorkoutTypeEnum::FOR_WEIGHT) {
            // todo : return a workout here
        }

        // if intervals we need the time and the rest time
        if ($workoutGeneration->getWorkoutType()->getNameAsEnum() === WorkoutTypeEnum::INTERVALS) {
            if ($workoutGeneration->getIntervalsTime() === null || $workoutGeneration->getIntervalsRestTime() === null) {
                throw new \InvalidArgumentException('Intervals time and rest time must be provided for INTERVALS workout type.');
            }

            // todo : implement intervals workout generation

            // todo : return a workout here
        }

        $blocks = $this->generateBlocks(
            $numberOfRounds,
            $movementsInWorkout,
            $workoutGeneration->getAvailableImplements()->toArray(),
            $workoutGeneration->getTimeCap(),
        );

        return new Workout(
            $workoutGeneration->getName(),
            $numberOfRounds,
            $workoutGeneration->getTimeCap(),
            $workoutGeneration->getWorkoutType(),
            $workoutOrigin,
            $blocks,
        );
    }

    /**
     * @param Movement[]       $movements
     * @param Implement[]|null $availableImplements
     *
     * @return Block[]
     */
    private function generateBlocks(
        int $numberOfRounds,
        array $movements,
        array $availableImplements,
        int $generalTimeCap,
        int $restTimeInSeconds = 0,
        int $maxMovementsPerBlock = 3,
    ): array {
        $blocks = [];
        $order = 1;
        $timePerBlock = intdiv($generalTimeCap, $numberOfRounds);

        $blocks[] = new Block(
            $numberOfRounds,
            $order,
            $this->generateWorkoutMovementsClusters(
                $movements,
                $availableImplements,
                $timePerBlock,
            ),
            null,
        );

        return $blocks;
    }

    /**
     * @param Movement[]       $movementsInWorkout
     * @param Implement[]|null $availableImplements
     *
     * @return MovementCluster[]
     */
    private function generateWorkoutMovementsClusters(
        array $movementsInWorkout,
        array $availableImplements,
        ?int $maximumTimeAllowedInSeconds = null,
    ): array {
        // what we already have
        $movementClusters = [];

        // let's set an implement for movement
        if ($maximumTimeAllowedInSeconds === null) {
            $maximumTimeAllowedInSeconds = rand(3, 15);
        }

        // todo : for each movement cluster, determine the movement type  (gym, cardio, wl) to generate it

        foreach ($movementsInWorkout as $movementInWorkout) {
            $possibleImplements = $movementInWorkout->getPossibleImplements()->toArray();
            shuffle($possibleImplements);
            $implement = null;
            $availableImplementsIds = array_map(fn (Implement $implement) => $implement->getId()->toString(), $availableImplements);
            foreach ($possibleImplements as $possibleImplement) {
                if (in_array($possibleImplement->getId()->toString(), $availableImplementsIds)) {
                    $implement = $possibleImplement;
                    break;
                }
            }

            if ($implement === null) {
                throw new \InvalidArgumentException('No available implement found for movement '.$movementInWorkout->getName());
            }

            $implementMeasureUnit = $implement->getImplementTypeOfAdjustableMeasure()->getMeasureUnits()->first()->getNameAsEnum();

            $movementClusters[] = $this->movementClusterGeneratorService->generateMovementCluster(
                $movementInWorkout,
                $movementInWorkout->getMovementExecutionTimeForMeasureUnits()->first()->getMeasureUnit(),
                $maximumTimeAllowedInSeconds,
                $implement,
                $implementMeasureUnit,
                null, // todo : add the intensity value depending on the rx standard
            );
        }

        return $movementClusters;
    }

    /*
      * The fewer movements, the more rounds
    */
    private function getNumberOfForTimeWorkoutRounds(int $numberOfDifferentMovements, int $maxNumberOfRounds): int
    {
        if ($numberOfDifferentMovements <= 3) {
            return rand(4, $maxNumberOfRounds);
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
