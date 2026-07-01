<?php

namespace App\Services\Workout\AiGeneration;

use App\Entity\WorkoutGeneration\WorkoutAiGenerationUsage;
use App\Repository\WorkoutGeneration\WorkoutAiGenerationUsageRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class WorkoutAiGenerationUsageTracker
{
    private AiTokenCostEstimator $costEstimator;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private WorkoutAiGenerationUsageRepository $usageRepository,
        private int $anonymousDailyLimit,
        private int $freeUserDailyLimit,
        private string $quotaTimezone,
        ?AiTokenCostEstimator $costEstimator = null,
    ) {
        $this->costEstimator = $costEstimator ?? new AiTokenCostEstimator();
    }

    public function quotaFor(WorkoutAiGenerationActor $actor, ?\DateTimeImmutable $now = null): WorkoutAiGenerationQuota
    {
        $now ??= new \DateTimeImmutable();
        $resetAt = $this->nextResetAt($now);

        if ($actor->isAdmin()) {
            return new WorkoutAiGenerationQuota(null, 0, null, $resetAt, true);
        }

        $limit = $actor->user === null ? $this->anonymousDailyLimit : $this->freeUserDailyLimit;
        $used = $this->usageRepository->countQuotaUsage(
            $actor->user,
            $actor->visitorHash,
            $this->dayStart($now),
            $resetAt,
        );

        return new WorkoutAiGenerationQuota($limit, $used, max(0, $limit - $used), $resetAt, $used < $limit);
    }

    public function quotaTimezone(): string
    {
        return $this->quotaTimezone;
    }

    /**
     * @param array<string, mixed>|null $aiUsage
     */
    public function recordSuccess(WorkoutAiGenerationActor $actor, string $endpoint, string $generationType, ?array $aiUsage = null): WorkoutAiGenerationUsage
    {
        return $this->record($actor, $endpoint, $generationType, 'success', true, $aiUsage);
    }

    /**
     * @param array<string, mixed>|null $aiUsage
     */
    public function recordFailure(WorkoutAiGenerationActor $actor, string $endpoint, string $generationType, \Throwable $exception, ?array $aiUsage = null): WorkoutAiGenerationUsage
    {
        return $this->record($actor, $endpoint, $generationType, 'failure', $aiUsage !== null, $aiUsage, $exception->getMessage());
    }

    /**
     * @param array<string, mixed>|null $aiUsage
     */
    private function record(
        WorkoutAiGenerationActor $actor,
        string $endpoint,
        string $generationType,
        string $status,
        bool $quotaCounted,
        ?array $aiUsage = null,
        ?string $failureReason = null,
    ): WorkoutAiGenerationUsage {
        $aiUsage = $this->withEstimatedCost($aiUsage);
        $usage = new WorkoutAiGenerationUsage(
            $actor->type,
            $endpoint,
            $generationType,
            $status,
            $quotaCounted,
            $actor->user,
            $actor->visitorHash,
            $aiUsage,
            $failureReason,
        );

        $this->entityManager->persist($usage);

        return $usage;
    }

    /**
     * @param array<string, mixed>|null $aiUsage
     *
     * @return array<string, mixed>|null
     */
    private function withEstimatedCost(?array $aiUsage): ?array
    {
        if ($aiUsage === null || ($aiUsage['estimated_cost_usd'] ?? null) !== null) {
            return $aiUsage;
        }

        $model = is_string($aiUsage['model'] ?? null) ? $aiUsage['model'] : null;
        $promptTokens = $this->nullableInt($aiUsage['prompt_tokens'] ?? null);
        $completionTokens = $this->nullableInt($aiUsage['completion_tokens'] ?? null);
        $estimatedCost = $this->costEstimator->estimateUsd($model, $promptTokens, $completionTokens);
        if ($estimatedCost === null) {
            return $aiUsage;
        }

        $aiUsage['estimated_cost_usd'] = $estimatedCost;

        return $aiUsage;
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

    private function dayStart(\DateTimeImmutable $now): \DateTimeImmutable
    {
        return $now
            ->setTimezone(new \DateTimeZone($this->quotaTimezone))
            ->setTime(0, 0)
            ->setTimezone(new \DateTimeZone('UTC'));
    }

    private function nextResetAt(\DateTimeImmutable $now): \DateTimeImmutable
    {
        return $this->dayStart($now)->modify('+1 day');
    }
}
