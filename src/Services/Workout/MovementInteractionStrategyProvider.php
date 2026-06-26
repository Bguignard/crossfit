<?php

namespace App\Services\Workout;

use App\Entity\WorkoutGeneration\WorkoutGeneration;

readonly class MovementInteractionStrategyProvider
{
    private const COMPLEMENTARY_FAST = 'complementary_fast';
    private const TARGETED_PREFATIGUE = 'targeted_prefatigue';
    private const SAME_LIMITER = 'same_limiter';
    private const ANTAGONISTIC_FLOW = 'antagonistic_flow';
    private const SKILL_UNDER_FATIGUE = 'skill_under_fatigue';
    private const ENGINE_PRIORITY = 'engine_priority';

    /**
     * @return list<string>
     */
    public function compatibleStrategies(WorkoutGeneration $workoutGeneration): array
    {
        $stimulus = $this->normalizedStimulus($workoutGeneration);

        if (str_contains($stimulus, 'engine')) {
            return [
                self::ENGINE_PRIORITY,
                self::ENGINE_PRIORITY,
                self::COMPLEMENTARY_FAST,
            ];
        }

        if (str_contains($stimulus, 'strength endurance')) {
            return [
                self::SAME_LIMITER,
                self::SAME_LIMITER,
                self::TARGETED_PREFATIGUE,
            ];
        }

        if (str_contains($stimulus, 'gymnastics') || str_contains($stimulus, 'skill')) {
            return [
                self::SKILL_UNDER_FATIGUE,
                self::SKILL_UNDER_FATIGUE,
                self::ANTAGONISTIC_FLOW,
            ];
        }

        if (str_contains($stimulus, 'competition') || str_contains($stimulus, 'compétition')) {
            return [
                self::ANTAGONISTIC_FLOW,
                self::SKILL_UNDER_FATIGUE,
                self::SAME_LIMITER,
                self::TARGETED_PREFATIGUE,
                self::COMPLEMENTARY_FAST,
            ];
        }

        if (str_contains($stimulus, 'metcon')) {
            return [
                self::COMPLEMENTARY_FAST,
                self::COMPLEMENTARY_FAST,
                self::ANTAGONISTIC_FLOW,
                self::TARGETED_PREFATIGUE,
            ];
        }

        return [
            self::COMPLEMENTARY_FAST,
            self::ANTAGONISTIC_FLOW,
            self::TARGETED_PREFATIGUE,
        ];
    }

    public function selectStrategy(WorkoutGeneration $workoutGeneration): string
    {
        $strategies = $this->compatibleStrategies($workoutGeneration);
        $hash = crc32(implode('|', [
            (string) $workoutGeneration->getName(),
            (string) $workoutGeneration->getStimulus(),
            (string) $workoutGeneration->getStimulusIntent(),
            (string) $workoutGeneration->getTimeCap(),
            $workoutGeneration->getWorkoutType()->getName(),
            $workoutGeneration->getMovementDifficulty()->getName(),
            (string) $workoutGeneration->getNumberOfDifferentMovements(),
            $workoutGeneration->isTeamWorkout() ? 'team' : 'individual',
        ]));

        return $strategies[$hash % count($strategies)];
    }

    public function buildPromptGuidance(WorkoutGeneration $workoutGeneration, bool $forVariants = false): string
    {
        if ($workoutGeneration->getNumberOfDifferentMovements() <= 1 || $this->isPureStrengthStimulus($workoutGeneration)) {
            return '';
        }

        $strategy = $this->selectStrategy($workoutGeneration);
        $context = $forVariants ? 'concepts' : 'final workout';

        return sprintf(
            "Movement interaction strategy guidance: use the internal strategy \"%s\" for these %s. This strategy is invisible to the athlete; do not print the strategy id or explain the internal taxonomy in the workout text.\n%s",
            $strategy,
            $context,
            $this->strategyGuidance($strategy, $forVariants),
        );
    }

    private function normalizedStimulus(WorkoutGeneration $workoutGeneration): string
    {
        return mb_strtolower(trim(sprintf(
            '%s %s',
            (string) $workoutGeneration->getStimulus(),
            (string) $workoutGeneration->getStimulusIntent(),
        )));
    }

    private function isPureStrengthStimulus(WorkoutGeneration $workoutGeneration): bool
    {
        $stimulus = $this->normalizedStimulus($workoutGeneration);

        return str_contains($stimulus, 'strength')
            && !str_contains($stimulus, 'strength endurance');
    }

    private function strategyGuidance(string $strategy, bool $forVariants): string
    {
        $verb = $forVariants ? 'Each concept should' : 'The final workout should';

        return match ($strategy) {
            self::COMPLEMENTARY_FAST => $verb." pair movements that interfere little with each other so athletes can keep moving fast. Avoid accidentally creating one dominant grip, shoulder, squat or posterior-chain bottleneck.\n",
            self::TARGETED_PREFATIGUE => $verb." use one movement to lightly pre-fatigue the next movement on purpose, with conservative volume and loading so technique remains stable for the requested level and time cap.\n",
            self::SAME_LIMITER => $verb." deliberately share one limiter such as grip, posterior chain, squat endurance, overhead stamina or midline, but keep the limiter dose moderate enough to preserve standards and avoid technical collapse.\n",
            self::ANTAGONISTIC_FLOW => $verb." alternate movement demands such as pull/push, legs/upper body, hinge/gymnastics or loaded/cyclical work so the interaction creates rhythm instead of random variety.\n",
            self::SKILL_UNDER_FATIGUE => $verb." place skill under manageable fatigue with small sets, realistic total volume and level-appropriate scaling. Do not turn skill work into a technical breakdown test.\n",
            self::ENGINE_PRIORITY => $verb." make breathing and pacing the main limiter with simple cyclical or low-skill movements. Avoid making grip, high-skill gymnastics, heavy loading or posterior-chain fatigue the hidden bottleneck.\n",
            default => '',
        };
    }
}
