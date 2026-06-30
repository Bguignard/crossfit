<?php

declare(strict_types=1);

namespace App\Services\Workout\Catalog;

use App\Entity\Workout\Workout;

final readonly class CanonicalWorkoutCatalogEntry
{
    /**
     * @param non-empty-list<Workout> $occurrences
     */
    public function __construct(
        public string $fingerprint,
        public Workout $representative,
        private array $occurrences,
    ) {
    }

    /**
     * @return non-empty-list<Workout>
     */
    public function occurrences(): array
    {
        return $this->occurrences;
    }

    public function occurrenceCount(): int
    {
        return count($this->occurrences);
    }

    /**
     * @return list<string>
     */
    public function workoutIds(): array
    {
        $ids = [];
        foreach ($this->occurrences as $workout) {
            $ids[] = (string) $workout->getId();
        }

        return $ids;
    }

    /**
     * @return list<string>
     */
    public function sourceNames(): array
    {
        $sourceNames = [];
        foreach ($this->occurrences as $workout) {
            $sourceName = $workout->getSourceName();
            if ($sourceName !== null && $sourceName !== '') {
                $sourceNames[$sourceName] = true;
            }
        }

        return $this->sortedKeys($sourceNames);
    }

    /**
     * @return list<string>
     */
    public function workoutOrigins(): array
    {
        $origins = [];
        foreach ($this->occurrences as $workout) {
            $origins[$workout->getWorkoutOrigin()->getName()->getName()] = true;
        }

        return $this->sortedKeys($origins);
    }

    /**
     * @return list<array{sourceName: string|null, externalId: string|null, sourceUrl: string|null}>
     */
    public function sourceReferences(): array
    {
        $references = [];
        $seen = [];

        foreach ($this->occurrences as $workout) {
            if ($workout->getSourceName() === null && $workout->getExternalId() === null && $workout->getSourceUrl() === null) {
                continue;
            }

            $reference = [
                'sourceName' => $workout->getSourceName(),
                'externalId' => $workout->getExternalId(),
                'sourceUrl' => $workout->getSourceUrl(),
            ];
            $key = implode('|', array_map(static fn (?string $value): string => $value ?? '', $reference));
            if (isset($seen[$key])) {
                continue;
            }

            $references[] = $reference;
            $seen[$key] = true;
        }

        return $references;
    }

    /**
     * @return list<array{
     *     competitionId: string,
     *     competitionName: string,
     *     competitionSeason: int|null,
     *     competitionLogoUrl: string|null,
     *     eventName: string,
     *     eventOrder: int|null,
     *     sourceName: string,
     *     divisions: list<string>,
     *     provenances: list<array<string, mixed>>
     * }>
     */
    public function competitionContexts(): array
    {
        $contextsByKey = [];
        $divisionsByKey = [];
        $provenancesByKey = [];
        $seen = [];
        $seenProvenances = [];

        foreach ($this->occurrences as $workout) {
            foreach ($workout->getCompetitionContexts() as $context) {
                $contextDivisions = $context['divisions'];
                $contextProvenances = $context['provenances'];
                $key = implode('|', [
                    $context['competitionId'],
                    $context['eventName'],
                    (string) ($context['eventOrder'] ?? ''),
                    $context['sourceName'],
                ]);
                if (!isset($seen[$key])) {
                    $context['divisions'] = [];
                    $context['provenances'] = [];
                    $contextsByKey[$key] = $context;
                    $divisionsByKey[$key] = [];
                    $provenancesByKey[$key] = [];
                    $seenProvenances[$key] = [];
                    $seen[$key] = true;
                }

                foreach ($contextDivisions as $division) {
                    $divisionsByKey[$key][$division] = true;
                }

                foreach ($contextProvenances as $provenance) {
                    $provenanceKey = json_encode($provenance, JSON_THROW_ON_ERROR);
                    if (isset($seenProvenances[$key][$provenanceKey])) {
                        continue;
                    }

                    $provenancesByKey[$key][] = $provenance;
                    $seenProvenances[$key][$provenanceKey] = true;
                }
            }
        }

        foreach ($contextsByKey as $key => $context) {
            $divisions = array_keys($divisionsByKey[$key]);
            sort($divisions, SORT_NATURAL | SORT_FLAG_CASE);
            $contextsByKey[$key]['divisions'] = $divisions;
            $contextsByKey[$key]['provenances'] = $provenancesByKey[$key];
        }

        return array_values($contextsByKey);
    }

    /**
     * @param array<string, true> $values
     *
     * @return list<string>
     */
    private function sortedKeys(array $values): array
    {
        $keys = array_keys($values);
        sort($keys, SORT_NATURAL | SORT_FLAG_CASE);

        return $keys;
    }
}
