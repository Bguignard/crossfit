<?php

namespace App\Services\Workout;

use App\Entity\Workout\Workout;

final class WorkoutPlaceholderFlowDetector
{
    private const PLACEHOLDER_FLOWS = ['*', '-', '–', '—'];

    public function displayableFlow(Workout $workout, ?string $eventName = null): ?string
    {
        $flow = trim($workout->getFlow());

        if ($flow === '' || in_array($flow, self::PLACEHOLDER_FLOWS, true)) {
            return null;
        }

        if ($this->matchesLabelPlaceholder($flow, [$workout->getName(), $eventName])) {
            return null;
        }

        return $flow;
    }

    /**
     * @param list<?string> $labels
     */
    private function matchesLabelPlaceholder(string $flow, array $labels): bool
    {
        $normalizedFlow = $this->normalizeLabel($flow);
        $strippedFlow = $this->stripWorkoutPrefix($normalizedFlow);

        if ($normalizedFlow === '') {
            return true;
        }

        foreach ($labels as $label) {
            if ($label === null) {
                continue;
            }

            $normalizedLabel = $this->normalizeLabel($label);
            if ($normalizedLabel === '') {
                continue;
            }

            if ($normalizedFlow === $normalizedLabel || $strippedFlow === $this->stripWorkoutPrefix($normalizedLabel)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeLabel(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9]+/', ' ', strtolower($value)) ?? '') ?? '');
    }

    private function stripWorkoutPrefix(string $value): string
    {
        return preg_replace('/^workout\s+/', '', $value) ?? $value;
    }
}
