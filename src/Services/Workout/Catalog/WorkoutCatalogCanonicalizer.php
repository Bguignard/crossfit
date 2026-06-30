<?php

declare(strict_types=1);

namespace App\Services\Workout\Catalog;

use App\Entity\Workout\Workout;

final class WorkoutCatalogCanonicalizer
{
    /**
     * @param list<Workout> $workouts
     *
     * @return list<CanonicalWorkoutCatalogEntry>
     */
    public function canonicalize(array $workouts): array
    {
        $groups = [];
        $order = [];

        foreach ($workouts as $workout) {
            $fingerprint = $this->fingerprint($workout);
            if (!isset($groups[$fingerprint])) {
                $groups[$fingerprint] = [];
                $order[] = $fingerprint;
            }

            $groups[$fingerprint][] = $workout;
        }

        $entries = [];
        foreach ($order as $fingerprint) {
            $occurrences = $groups[$fingerprint];
            $entries[] = new CanonicalWorkoutCatalogEntry($fingerprint, $occurrences[0], $occurrences);
        }

        return $entries;
    }

    public function fingerprint(Workout $workout): string
    {
        $importedFingerprint = $workout->getCanonicalFingerprint();
        if ($importedFingerprint !== null && trim($importedFingerprint) !== '') {
            return trim($importedFingerprint);
        }

        return hash('sha256', implode('|', [
            $this->normalizeText((string) $workout->getName()),
            $this->normalizeText($workout->getFlow()),
            $this->normalizeText((string) ($workout->getWorkoutType()?->getName() ?? '')),
            (string) ($workout->getNumberOfRounds() ?? ''),
            (string) ($workout->getTimeCap() ?? ''),
        ]));
    }

    private function normalizeText(string $value): string
    {
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = mb_strtolower($value);
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace('/[[:punct:]]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }
}
