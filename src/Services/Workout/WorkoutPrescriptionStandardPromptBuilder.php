<?php

namespace App\Services\Workout;

use App\Entity\Workout\Movement;
use App\Entity\Workout\WorkoutPrescriptionStandard;
use App\Entity\WorkoutGeneration\WorkoutGeneration;
use App\Repository\Workout\WorkoutPrescriptionStandardRepository;

readonly class WorkoutPrescriptionStandardPromptBuilder
{
    public function __construct(
        private WorkoutPrescriptionStandardRepository $standardRepository,
    ) {
    }

    /**
     * @param Movement[] $movements
     */
    public function build(WorkoutGeneration $workoutGeneration, array $movements): string
    {
        $standards = $this->standardRepository->findForPrompt(
            $workoutGeneration->getMovementDifficulty()->getName(),
            $this->movementNames($movements),
            $this->implementNames($workoutGeneration, $movements),
            $this->isHyroxIntent($workoutGeneration),
        );

        if ($standards === []) {
            return '';
        }

        $prompt = "Known load prescription standards to prefer when relevant:\n";
        foreach ($this->groupByScope($standards) as $scope => $scopeStandards) {
            $prompt .= sprintf("- %s\n", $scope);
            foreach ($scopeStandards as $standard) {
                $prompt .= sprintf("  - %s\n", $standard->label());
            }
        }

        return $prompt."Use these as anchors, not as mandatory loads for every movement. If the requested stimulus, level or implement makes a different load safer or more coherent, explain it through the scaling options.\n";
    }

    /**
     * @param Movement[] $movements
     *
     * @return list<string>
     */
    private function movementNames(array $movements): array
    {
        return array_values(array_unique(array_map(
            static fn (Movement $movement): string => (string) $movement->getName(),
            $movements,
        )));
    }

    /**
     * @param Movement[] $movements
     *
     * @return list<string>
     */
    private function implementNames(WorkoutGeneration $workoutGeneration, array $movements): array
    {
        $implementNames = [];
        foreach ($workoutGeneration->getAvailableImplements() as $implement) {
            $implementNames[] = $implement->getName();
        }
        foreach ($movements as $movement) {
            foreach ($movement->getPossibleImplements() as $implement) {
                $implementNames[] = $implement->getName();
            }
        }

        return array_values(array_unique($implementNames));
    }

    private function isHyroxIntent(WorkoutGeneration $workoutGeneration): bool
    {
        $haystack = strtolower(trim(sprintf(
            '%s %s %s',
            $workoutGeneration->getName(),
            $workoutGeneration->getStimulus() ?? '',
            $workoutGeneration->getStimulusIntent() ?? '',
        )));

        return str_contains($haystack, 'hyrox');
    }

    /**
     * @param list<WorkoutPrescriptionStandard> $standards
     *
     * @return array<string, list<WorkoutPrescriptionStandard>>
     */
    private function groupByScope(array $standards): array
    {
        $groups = [];
        foreach ($standards as $standard) {
            $level = $standard->getLevelName() ?? 'all levels';
            $groups[$standard->getSport().' / '.$level.' / '.$standard->getSourceName()][] = $standard;
        }

        return $groups;
    }
}
