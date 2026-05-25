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
            foreach ($this->formatStandards($scopeStandards) as $standardLabel) {
                $prompt .= sprintf("  - %s\n", $standardLabel);
            }
        }

        return $prompt."Use exact movement standards before generic implement standards. Use these as anchors, not as mandatory loads for every movement. If the requested stimulus, level or implement makes a different load safer or more coherent, explain it through the scaling options.\n";
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

    /**
     * @param list<WorkoutPrescriptionStandard> $standards
     *
     * @return list<string>
     */
    private function formatStandards(array $standards): array
    {
        $groups = [];
        foreach ($standards as $standard) {
            $groups[$this->standardGroupKey($standard)][] = $standard;
        }

        $labels = [];
        foreach ($groups as $groupedStandards) {
            if (count($groupedStandards) === 1) {
                $labels[] = $groupedStandards[0]->label();

                continue;
            }

            usort(
                $groupedStandards,
                static fn (WorkoutPrescriptionStandard $a, WorkoutPrescriptionStandard $b): int => $a->getQuantity() <=> $b->getQuantity(),
            );

            $labels[] = $this->groupedStandardLabel($groupedStandards);
        }

        return $labels;
    }

    private function standardGroupKey(WorkoutPrescriptionStandard $standard): string
    {
        return implode('|', [
            $standard->getDivision(),
            $standard->getMovementName() ?? '',
            $standard->getImplementName() ?? '',
            $standard->getUnit(),
            (string) $standard->getQuantityMultiplier(),
            $standard->getContextLabel() ?? '',
            $standard->getNotes() ?? '',
        ]);
    }

    /**
     * @param non-empty-list<WorkoutPrescriptionStandard> $standards
     */
    private function groupedStandardLabel(array $standards): string
    {
        $first = $standards[0];
        $target = $first->getMovementName() ?? $first->getImplementName() ?? 'loaded movement';
        $loads = implode(' / ', array_map(
            fn (WorkoutPrescriptionStandard $standard): string => $this->formattedLoad($standard),
            $standards,
        ));
        $context = $first->getContextLabel() === null ? '' : ' ('.$first->getContextLabel().')';
        $notes = $first->getNotes() === null ? '' : ' - '.$first->getNotes();

        return sprintf('%s %s progression: %s%s%s', ucfirst($first->getDivision()), $target, $loads, $context, $notes);
    }

    private function formattedLoad(WorkoutPrescriptionStandard $standard): string
    {
        $quantity = rtrim(rtrim(number_format($standard->getQuantity(), 2, '.', ''), '0'), '.');

        return ($standard->getQuantityMultiplier() > 1 ? $standard->getQuantityMultiplier().' x ' : '').$quantity.' '.$standard->getUnit();
    }
}
