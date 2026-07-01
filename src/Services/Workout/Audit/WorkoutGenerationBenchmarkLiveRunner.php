<?php

namespace App\Services\Workout\Audit;

use App\Entity\Workout\Enum\MovementDifficultyEnum;
use App\Entity\Workout\Enum\WorkoutMovementGenerationTypeEnum;
use App\Entity\Workout\Enum\WorkoutTypeEnum;
use App\Entity\Workout\MovementDifficulty;
use App\Entity\Workout\WorkoutMovementGenerationType;
use App\Entity\Workout\WorkoutType;
use App\Entity\WorkoutGeneration\WorkoutGeneration;
use App\Repository\Workout\ImplementRepositoryInterface;
use App\Services\Workout\ChatGPTApiKey;
use App\Services\Workout\CompetitionMovementFrequencyGuidanceProvider;
use App\Services\Workout\MovementInteractionStrategyProvider;
use App\Services\Workout\MovementServiceInterface;
use App\Services\Workout\TeamWorkoutStructureGuidanceProvider;
use App\Services\Workout\WorkoutCreatorService;
use App\Services\Workout\WorkoutLoadPrescriptionValidator;
use App\Services\Workout\WorkoutOriginServiceInterface;
use App\Services\Workout\WorkoutPrescriptionStandardPromptBuilder;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class WorkoutGenerationBenchmarkLiveRunner implements WorkoutGenerationBenchmarkLiveRunnerInterface
{
    public function __construct(
        private MovementServiceInterface $movementService,
        private WorkoutOriginServiceInterface $workoutOriginService,
        private WorkoutStimulusAuditor $auditor,
        private ImplementRepositoryInterface $implementRepository,
        private HttpClientInterface $httpClient,
        private ?WorkoutPrescriptionStandardPromptBuilder $prescriptionStandardPromptBuilder = null,
        private ?CompetitionMovementFrequencyGuidanceProvider $competitionMovementFrequencyGuidanceProvider = null,
        private ?TeamWorkoutStructureGuidanceProvider $teamWorkoutStructureGuidanceProvider = null,
        private ?MovementInteractionStrategyProvider $movementInteractionStrategyProvider = null,
        private ?WorkoutLoadPrescriptionValidator $loadPrescriptionValidator = null,
        private string $chatGPTApiKey = '',
    ) {
    }

    public function isConfigured(): bool
    {
        return trim($this->chatGPTApiKey) !== '';
    }

    public function requiresOpenAi(string $strategy): bool
    {
        return $strategy === 'full_ai';
    }

    /**
     * @return array<string, mixed>
     */
    public function run(string $model, string $strategy, WorkoutStimulusAuditScenario $scenario): array
    {
        if ($strategy === 'no_ai_baseline') {
            return $this->entry(
                model: $model,
                strategy: $strategy,
                scenario: $scenario,
                status: 'not_generated',
                passed: false,
                failureReason: 'No deterministic no-AI workout generation baseline exists yet.',
                checks: ['generated_workout_available' => false],
            );
        }

        if ($strategy !== 'full_ai') {
            return $this->entry(
                model: $model,
                strategy: $strategy,
                scenario: $scenario,
                status: 'live_strategy_not_implemented',
                passed: false,
                failureReason: sprintf('Strategy "%s" is represented in the matrix but does not have a live generator yet.', $strategy),
                checks: ['generated_workout_available' => false],
            );
        }

        if (!$this->isConfigured()) {
            return $this->entry(
                model: $model,
                strategy: $strategy,
                scenario: $scenario,
                status: 'configuration_error',
                passed: false,
                failureReason: 'CHAT_GPT_API_KEY is required for live benchmark runs.',
                checks: ['generated_workout_available' => false],
            );
        }

        $startedAt = microtime(true);
        $usage = null;
        $chatGpt = null;
        $creator = null;

        try {
            $chatGpt = new ChatGPTApiKey($this->chatGPTApiKey, $model, $this->httpClient);
            $creator = new WorkoutCreatorService(
                $this->movementService,
                $chatGpt,
                $this->workoutOriginService,
                $this->prescriptionStandardPromptBuilder,
                $this->competitionMovementFrequencyGuidanceProvider,
                $this->teamWorkoutStructureGuidanceProvider,
                $this->movementInteractionStrategyProvider,
                $this->loadPrescriptionValidator,
            );
            $workout = $creator->createWorkout($this->workoutGenerationFromScenario($scenario));
            $usage = $this->aggregateUsage($chatGpt->getUsageHistory(), $workout->getAiUsage());
            $retryCount = $this->retryCount($chatGpt->getUsageHistory(), $usage);
            $result = $this->auditor->evaluate($scenario, $workout);

            return $this->entry(
                model: $model,
                strategy: $strategy,
                scenario: $scenario,
                status: $result->passed ? 'success' : 'validation_failed',
                passed: $result->passed,
                failureReason: $result->passed ? null : 'Generated workout did not pass scenario validation checks.',
                checks: $result->checks,
                tokenUsage: $this->tokenUsage($usage),
                retryCount: $retryCount,
                durationMs: $this->durationMs($usage, $startedAt),
                estimatedCostUsd: $this->estimatedCost($usage),
            );
        } catch (\Throwable $exception) {
            $usageHistory = $chatGpt?->getUsageHistory() ?? [];
            $usage ??= $this->aggregateUsage($usageHistory, $creator?->getLastAiUsage() ?? $chatGpt?->getLastUsage());
            $retryCount = $this->retryCount($usageHistory, $usage);

            return $this->entry(
                model: $model,
                strategy: $strategy,
                scenario: $scenario,
                status: 'error',
                passed: false,
                failureReason: $exception->getMessage(),
                checks: ['generated_workout_available' => false],
                tokenUsage: $this->tokenUsage($usage),
                retryCount: $retryCount,
                durationMs: $this->durationMs($usage, $startedAt),
                estimatedCostUsd: $this->estimatedCost($usage),
            );
        }
    }

    private function workoutGenerationFromScenario(WorkoutStimulusAuditScenario $scenario): WorkoutGeneration
    {
        $workoutType = WorkoutTypeEnum::from($scenario->workoutType);

        return (new WorkoutGeneration())
            ->setName(sprintf('Benchmark %s', $scenario->stimulus))
            ->setStimulus($scenario->stimulus)
            ->setStimulusIntent($scenario->intent)
            ->setWorkoutType(new WorkoutType($workoutType))
            ->setTimeCap($scenario->timeCap)
            ->setNumberOfDifferentMovements($scenario->movementCount)
            ->setMovementDifficulty(new MovementDifficulty(MovementDifficultyEnum::RX))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setAvailableImplements($this->implementRepository->findAll())
            ->setNumberOfRounds(null)
            ->setIntervalsTime($workoutType === WorkoutTypeEnum::INTERVALS ? 60 : null)
            ->setIntervalsRestTime($workoutType === WorkoutTypeEnum::INTERVALS ? 30 : null)
            ->setIsTeamWorkout(false);
    }

    /**
     * @param list<array<string, mixed>> $usageHistory
     * @param array<string, mixed>|null  $fallbackUsage
     *
     * @return array<string, mixed>|null
     */
    private function aggregateUsage(array $usageHistory, ?array $fallbackUsage): ?array
    {
        if ($usageHistory === []) {
            return $fallbackUsage;
        }
        if (count($usageHistory) === 1) {
            return $usageHistory[0];
        }

        $lastUsage = $usageHistory[array_key_last($usageHistory)];

        return array_merge($lastUsage, [
            'prompt_tokens' => $this->sumNullableInts($usageHistory, 'prompt_tokens'),
            'completion_tokens' => $this->sumNullableInts($usageHistory, 'completion_tokens'),
            'total_tokens' => $this->sumNullableInts($usageHistory, 'total_tokens'),
            'duration_ms' => $this->sumNullableInts($usageHistory, 'duration_ms'),
            'estimated_cost_usd' => $this->sumNullableNumericStrings($usageHistory, 'estimated_cost_usd'),
            'call_count' => count($usageHistory),
        ]);
    }

    /**
     * @param list<array<string, mixed>> $usageHistory
     * @param array<string, mixed>|null  $usage
     */
    private function retryCount(array $usageHistory, ?array $usage): ?int
    {
        if ($usageHistory !== []) {
            return max(0, count($usageHistory) - 1);
        }

        return $usage === null ? null : 0;
    }

    /**
     * @param array<string, mixed>|null $usage
     *
     * @return array{promptTokens: int|null, completionTokens: int|null, totalTokens: int|null}
     */
    private function tokenUsage(?array $usage): array
    {
        return [
            'promptTokens' => $this->nullableInt($usage['prompt_tokens'] ?? null),
            'completionTokens' => $this->nullableInt($usage['completion_tokens'] ?? null),
            'totalTokens' => $this->nullableInt($usage['total_tokens'] ?? null),
        ];
    }

    /**
     * @param array<string, mixed>|null $usage
     */
    private function durationMs(?array $usage, float $startedAt): int
    {
        return $this->nullableInt($usage['duration_ms'] ?? null) ?? (int) round((microtime(true) - $startedAt) * 1000);
    }

    /**
     * @param array<string, mixed>|null $usage
     */
    private function estimatedCost(?array $usage): ?string
    {
        $value = $usage['estimated_cost_usd'] ?? null;
        if ($value === null) {
            return null;
        }

        return is_numeric($value) ? (string) $value : null;
    }

    /**
     * @param list<array<string, mixed>> $usages
     */
    private function sumNullableInts(array $usages, string $key): ?int
    {
        $sum = 0;
        $hasValue = false;

        foreach ($usages as $usage) {
            $value = $this->nullableInt($usage[$key] ?? null);
            if ($value === null) {
                continue;
            }

            $sum += $value;
            $hasValue = true;
        }

        return $hasValue ? $sum : null;
    }

    /**
     * @param list<array<string, mixed>> $usages
     */
    private function sumNullableNumericStrings(array $usages, string $key): ?string
    {
        $sum = 0.0;
        $hasValue = false;

        foreach ($usages as $usage) {
            $value = $usage[$key] ?? null;
            if (!is_numeric($value)) {
                continue;
            }

            $sum += (float) $value;
            $hasValue = true;
        }

        return $hasValue ? rtrim(rtrim(sprintf('%.10F', $sum), '0'), '.') : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && preg_match('/^\d+$/', $value) === 1) {
            return (int) $value;
        }

        return null;
    }

    /**
     * @param array<string, bool>                                                                   $checks
     * @param array{promptTokens: int|null, completionTokens: int|null, totalTokens: int|null}|null $tokenUsage
     *
     * @return array<string, mixed>
     */
    private function entry(
        string $model,
        string $strategy,
        WorkoutStimulusAuditScenario $scenario,
        string $status,
        bool $passed,
        ?string $failureReason,
        array $checks,
        ?array $tokenUsage = null,
        ?int $retryCount = null,
        ?int $durationMs = null,
        ?string $estimatedCostUsd = null,
    ): array {
        return [
            'model' => $model,
            'strategy' => $strategy,
            'scenario' => $scenario->slug,
            'status' => $status,
            'passed' => $passed,
            'failureReason' => $failureReason,
            'tokenUsage' => $tokenUsage ?? [
                'promptTokens' => null,
                'completionTokens' => null,
                'totalTokens' => null,
            ],
            'retryCount' => $retryCount,
            'durationMs' => $durationMs,
            'estimatedCostUsd' => $estimatedCostUsd,
            'checks' => $checks,
        ];
    }
}
