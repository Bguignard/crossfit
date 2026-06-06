<?php

namespace App\Services\Workout\Audit;

use App\Entity\Workout\Workout;
use App\Entity\Workout\WorkoutType;

final readonly class WorkoutStimulusAuditor
{
    /**
     * @return list<WorkoutStimulusAuditScenario>
     */
    public function scenarios(): array
    {
        return [
            new WorkoutStimulusAuditScenario(
                slug: 'strength',
                stimulus: 'Strength',
                intent: 'Developper la force maximale avec charges lourdes, faible volume et longs repos.',
                workoutType: 'For weight',
                timeCap: 20,
                movementCount: 1,
                expectedTerms: ['heavy', 'lourd', '1rm', '3rm', '5x3', 'repos', 'rest']
            ),
            new WorkoutStimulusAuditScenario(
                slug: 'sprint',
                stimulus: 'Sprint',
                intent: 'Developper la puissance anaerobie sur un effort court de 2 a 8 minutes.',
                workoutType: 'For time',
                timeCap: 8,
                movementCount: 2,
                expectedTerms: ['sprint', 'fast', 'rapide', 'intense', 'unbroken', 'time cap']
            ),
            new WorkoutStimulusAuditScenario(
                slug: 'threshold',
                stimulus: 'Threshold',
                intent: 'Travailler au seuil lactique avec pacing eleve mais controlable.',
                workoutType: 'AMRAP',
                timeCap: 16,
                movementCount: 3,
                expectedTerms: ['threshold', 'seuil', 'pacing', 'soutenu', 'controlled', 'controle']
            ),
            new WorkoutStimulusAuditScenario(
                slug: 'engine',
                stimulus: 'Engine',
                intent: 'Developper la capacite aerobie sur effort long et modere.',
                workoutType: 'AMRAP',
                timeCap: 40,
                movementCount: 3,
                expectedTerms: ['engine', 'aerobic', 'aerobie', 'zone', 'pacing', 'steady']
            ),
            new WorkoutStimulusAuditScenario(
                slug: 'hyrox_training',
                stimulus: 'Entrainement Hyrox',
                intent: 'Travailler quelques stations Hyrox avec run ou erg sans simuler une course complete.',
                workoutType: 'For time',
                timeCap: 30,
                movementCount: 5,
                expectedTerms: ['run', 'station', 'sled', 'row', 'wall ball', 'farmer'],
                forbiddenTerms: ['8 stations', 'complete hyrox', 'simulation complete']
            ),
            new WorkoutStimulusAuditScenario(
                slug: 'hyrox_simulation',
                stimulus: 'Simulation Hyrox',
                intent: 'Simuler une course Hyrox complete avec run recurrent et 8 stations ordonnees.',
                workoutType: 'For time',
                timeCap: 75,
                movementCount: 9,
                expectedTerms: ['run', 'ski', 'sled push', 'sled pull', 'burpee', 'row', 'farmer', 'lunge', 'wall ball'],
                expectedStationCount: 8
            ),
            new WorkoutStimulusAuditScenario(
                slug: 'strength_endurance',
                stimulus: 'Strength Endurance',
                intent: 'Maintenir une production de force sous fatigue avec charges moderees et volume important.',
                workoutType: 'For time',
                timeCap: 18,
                movementCount: 3,
                expectedTerms: ['moderate', 'volume', 'fatigue', 'barbell', 'load', 'charge']
            ),
            new WorkoutStimulusAuditScenario(
                slug: 'gymnastics_skill',
                stimulus: 'Gymnastics / Skill',
                intent: 'Developper la maitrise technique et le controle corporel.',
                workoutType: 'Intervals',
                timeCap: 20,
                movementCount: 3,
                expectedTerms: ['skill', 'gymnastics', 'technique', 'strict', 'control', 'quality'],
                forbiddenTerms: ['50 muscle-ups', '100 hspu']
            ),
            new WorkoutStimulusAuditScenario(
                slug: 'competition',
                stimulus: 'Mixed Modal / Competition',
                intent: 'Tester plusieurs qualites simultanement avec strategie et gestion de fatigue.',
                workoutType: 'For time',
                timeCap: 25,
                movementCount: 4,
                expectedTerms: ['competition', 'mixed', 'strategy', 'pacing', 'manage', 'gestion']
            ),
        ];
    }

    public function evaluate(WorkoutStimulusAuditScenario $scenario, ?Workout $workout): WorkoutStimulusAuditResult
    {
        if ($workout === null) {
            return new WorkoutStimulusAuditResult(
                scenarioSlug: $scenario->slug,
                passed: false,
                checks: ['generated_workout_available' => false],
                termHits: [],
                scalingHits: [],
                forbiddenHits: [],
                stationCount: 0
            );
        }

        $haystack = $this->normalise(sprintf(
            '%s %s %s',
            $workout->getName() ?? '',
            $workout->getFlow(),
            $this->workoutTypeName($workout)
        ));
        $termHits = $this->hits($haystack, $scenario->expectedTerms);
        $scalingHits = $this->hits($haystack, $scenario->expectedScalingTerms);
        $forbiddenHits = $this->hits($haystack, $scenario->forbiddenTerms);
        $stationCount = $this->stationCount($workout->getFlow());

        $checks = [
            'generated_workout_available' => true,
            'time_cap_present' => $workout->getTimeCap() !== null && $workout->getTimeCap() > 0,
            'stimulus_terms_present' => count($termHits) >= min(3, count($scenario->expectedTerms)),
            'scaling_present' => count($scalingHits) >= min(2, count($scenario->expectedScalingTerms)),
            'no_forbidden_overemphasis' => count($forbiddenHits) === 0,
        ];

        if ($scenario->expectedStationCount !== null) {
            $checks['expected_station_count'] = $stationCount >= $scenario->expectedStationCount;
        }

        return new WorkoutStimulusAuditResult(
            scenarioSlug: $scenario->slug,
            passed: !in_array(false, $checks, true),
            checks: $checks,
            termHits: $termHits,
            scalingHits: $scalingHits,
            forbiddenHits: $forbiddenHits,
            stationCount: $stationCount
        );
    }

    /**
     * @param list<string> $terms
     *
     * @return list<string>
     */
    private function hits(string $haystack, array $terms): array
    {
        return array_values(array_filter(
            $terms,
            fn (string $term): bool => str_contains($haystack, $this->normalise($term))
        ));
    }

    private function normalise(string $value): string
    {
        return strtolower($value);
    }

    private function workoutTypeName(Workout $workout): string
    {
        $workoutType = $workout->getWorkoutType();

        return $workoutType instanceof WorkoutType ? $workoutType->getName() : '';
    }

    private function stationCount(string $flow): int
    {
        preg_match_all('/\bstation\s*\d+\b/i', $flow, $stationMatches);
        if (count($stationMatches[0]) > 0) {
            return count(array_unique(array_map('strtolower', $stationMatches[0])));
        }

        preg_match_all('/^\s*\d+[\).:-]\s+/m', $flow, $numberedMatches);

        return count($numberedMatches[0]);
    }
}
