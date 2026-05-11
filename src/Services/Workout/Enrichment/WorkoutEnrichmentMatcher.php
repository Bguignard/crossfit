<?php

namespace App\Services\Workout\Enrichment;

use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\Workout;

final class WorkoutEnrichmentMatcher
{
    /**
     * @param iterable<Movement>  $movements
     * @param iterable<Implement> $implements
     */
    public function match(Workout $workout, iterable $movements, iterable $implements): WorkoutEnrichmentMatch
    {
        $text = $this->normalizeText(sprintf('%s %s', $workout->getName(), $workout->getFlow()));
        $movementByName = $this->indexMovements($movements);
        $implementByName = $this->indexImplements($implements);

        $matchedMovements = [];
        foreach ($this->movementAliases() as $alias => $movementName) {
            $normalizedAlias = $this->normalizeText($alias);
            if ($this->containsTerm($text, $normalizedAlias) && isset($movementByName[$this->key($movementName)])) {
                $movement = $movementByName[$this->key($movementName)];
                $matchedMovements[(string) $movement->getId()] = $movement;
            }
        }

        $matchedImplements = [];
        foreach ($this->implementAliases() as $alias => $implementName) {
            $normalizedAlias = $this->normalizeText($alias);
            if ($this->containsTerm($text, $normalizedAlias) && isset($implementByName[$this->key($implementName)])) {
                $implement = $implementByName[$this->key($implementName)];
                $matchedImplements[(string) $implement->getId()] = $implement;
            }
        }

        $ambiguousTerms = [];
        foreach ($this->ambiguousAliases() as $alias) {
            if ($this->containsTerm($text, $this->normalizeText($alias))) {
                $ambiguousTerms[] = $alias;
            }
        }

        return new WorkoutEnrichmentMatch(
            array_values($matchedMovements),
            array_values($matchedImplements),
            array_values(array_unique($ambiguousTerms)),
        );
    }

    /**
     * @param iterable<Movement> $movements
     *
     * @return array<string, Movement>
     */
    private function indexMovements(iterable $movements): array
    {
        $indexedMovements = [];
        foreach ($movements as $movement) {
            $indexedMovements[$this->key((string) $movement->getName())] = $movement;
        }

        return $indexedMovements;
    }

    /**
     * @param iterable<Implement> $implements
     *
     * @return array<string, Implement>
     */
    private function indexImplements(iterable $implements): array
    {
        $indexedImplements = [];
        foreach ($implements as $implement) {
            $indexedImplements[$this->key($implement->getName())] = $implement;
        }

        return $indexedImplements;
    }

    /**
     * @return array<string, string>
     */
    private function movementAliases(): array
    {
        return [
            'handstand push ups' => 'Handstand Push Up',
            'handstand push up' => 'Handstand Push Up',
            'strict handstand push ups' => 'Strict Handstand Push Up',
            'strict handstand push up' => 'Strict Handstand Push Up',
            'ring handstand push ups' => 'Handstand Push Up',
            'pull ups' => 'Pull Up',
            'pull up' => 'Pull Up',
            'strict pull ups' => 'Strict Pull Up',
            'strict pull up' => 'Strict Pull Up',
            'l pull ups' => 'Pull Up',
            'chest to bar pull ups' => 'Chest To Bar Pull Up',
            'chest to bar pull up' => 'Chest To Bar Pull Up',
            'burpee pull ups' => 'Burpee Pull Up',
            'muscle ups' => 'Muscle Up',
            'muscle up' => 'Muscle Up',
            'ring push ups' => 'Push Up',
            'push ups' => 'Push Up',
            'push up' => 'Push Up',
            'hand release push ups' => 'Hand Release Push Up',
            'ring dips' => 'Dip',
            'deadlifts' => 'Deadlift',
            'deadlift' => 'Deadlift',
            'stiff legged deadlift' => 'Deadlift',
            'bench presses' => 'Bench Press',
            'bench press' => 'Bench Press',
            'thrusters' => 'Thruster',
            'thruster' => 'Thruster',
            'push presses' => 'Push Press',
            'push press' => 'Push Press',
            'push jerks' => 'Push Jerk',
            'push jerk' => 'Push Jerk',
            'squat cleans' => 'Squat Clean',
            'squat clean' => 'Squat Clean',
            'dumbbell squat cleans' => 'Squat Clean',
            'hang power cleans' => 'Hang Power Clean',
            'hang power clean' => 'Hang Power Clean',
            'cleans' => 'Clean',
            'clean' => 'Clean',
            'power snatches' => 'Power Snatch',
            'power snatch' => 'Power Snatch',
            'dumbbell snatches' => 'Snatch',
            'snatches' => 'Snatch',
            'snatch' => 'Snatch',
            'overhead squats' => 'Overhead Squat',
            'overhead squat' => 'Overhead Squat',
            'back squats' => 'Back Squat',
            'back squat' => 'Back Squat',
            'front squats' => 'Front Squat',
            'front squat' => 'Front Squat',
            'squats' => 'Air Squat',
            'squat' => 'Air Squat',
            'double unders' => 'Double Under',
            'double under' => 'Double Under',
            'box jumps' => 'Box Jump',
            'box jump' => 'Box Jump',
            'wall ball shots' => 'Wall Ball Shot',
            'wall ball shot' => 'Wall Ball Shot',
            'kettlebell swings' => 'American Swing',
            'kettlebell swing' => 'American Swing',
            'run' => 'Run',
            'running' => 'Run',
            'burpees' => 'Burpee',
            'burpee' => 'Burpee',
            'toes to bars' => 'Toes To Bar',
            'toes to bar' => 'Toes To Bar',
            'knees to elbows' => 'Knees To Elbows',
            'ghd sit ups' => 'Ghd Sit Up',
            'sit ups' => 'Sit Up',
            'sit up' => 'Sit Up',
            'back extensions' => 'Ghd Back Extension',
            'rope climbs' => 'Rope Climb',
            'rope climb' => 'Rope Climb',
            'turkish get ups' => 'Turkish Get Up',
            'turkish get up' => 'Turkish Get Up',
            'overhead walking lunge' => 'Overhead Walking Lunge',
            'walking lunge' => 'Walking Lunge',
            'overhead walk' => 'Carry',
            'farmers carry' => 'Farmer Carry',
            'sandbag carry' => 'Carry',
            'bear crawl' => 'Bear Crawl',
            'broad jump' => 'Broad Jump',
            'sumo deadlift high pulls' => 'Sumo Deadlift High Pull',
            'sumo deadlift high pull' => 'Sumo Deadlift High Pull',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function implementAliases(): array
    {
        return [
            'barbell' => 'barbell',
            'barbells' => 'barbell',
            'dumbbell' => 'dumbbell',
            'dumbbells' => 'dumbbell',
            'kettlebell' => 'kettlebell',
            'kettlebells' => 'kettlebell',
            'pull ups' => 'pull up bar',
            'pull up' => 'pull up bar',
            'chest to bar' => 'pull up bar',
            'toes to bar' => 'pull up bar',
            'knees to elbows' => 'pull up bar',
            'muscle ups' => 'rings',
            'muscle up' => 'rings',
            'ring dips' => 'rings',
            'rings' => 'rings',
            'rope climbs' => 'rope',
            'rope climb' => 'rope',
            'box jumps' => 'box',
            'box jump' => 'box',
            'box' => 'box',
            'wall ball' => 'medicine ball',
            'medicine ball' => 'medicine ball',
            'double unders' => 'jump rope',
            'double under' => 'jump rope',
            'jump rope' => 'jump rope',
            'bench presses' => 'bench',
            'bench press' => 'bench',
            'ghd' => 'ghd',
            'weight vest' => 'weighted vest',
            'weighted vest' => 'weighted vest',
            'body armor' => 'weighted vest',
            'plate' => 'plate',
            'sandbag' => 'sand bag',
        ];
    }

    /**
     * @return list<string>
     */
    private function ambiguousAliases(): array
    {
        return [
            'clean',
            'snatch',
            'squat',
            'swing',
            'carry',
        ];
    }

    private function containsTerm(string $normalizedText, string $normalizedTerm): bool
    {
        return preg_match('/(^| )'.preg_quote($normalizedTerm, '/').'($| )/', $normalizedText) === 1;
    }

    private function normalizeText(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', ' ', $text) ?? '';

        return trim((string) preg_replace('/\s+/', ' ', $text));
    }

    private function key(string $name): string
    {
        return str_replace(' ', '', $this->normalizeText($name));
    }
}
