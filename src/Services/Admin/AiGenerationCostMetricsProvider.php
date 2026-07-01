<?php

namespace App\Services\Admin;

use App\Entity\Product\Enum\AnalysisRequestStatusEnum;
use App\Entity\Product\Enum\ProgrammingGenerationRequestStatusEnum;
use App\Entity\Product\Enum\ProgrammingGenerationTypeEnum;
use App\Entity\Product\PerformanceAnalysisRequest;
use App\Entity\Product\ProgrammingGenerationRequest;
use App\Entity\Product\ProgrammingSessionDetailRequest;
use App\Entity\WorkoutGeneration\WorkoutAiGenerationUsage;
use Doctrine\ORM\EntityManagerInterface;

class AiGenerationCostMetricsProvider
{
    private const CATEGORY_WORKOUT_GENERATION = 'workout_generation';
    private const CATEGORY_ATHLETE_ANALYSIS = 'athlete_analysis';
    private const CATEGORY_ATHLETE_PROGRAMMING = 'athlete_programming';
    private const CATEGORY_BOX_PROGRAMMING = 'box_programming';
    private const CATEGORY_COMPETITION_PROGRAMMING = 'competition_programming';

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function summarize(?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null): array
    {
        $to ??= new \DateTimeImmutable();
        $from ??= $to->sub(new \DateInterval('P30D'));

        if ($from >= $to) {
            throw new \InvalidArgumentException('The "from" date must be earlier than the "to" date.');
        }

        $categories = $this->emptyCategories();

        foreach ($this->workoutUsageRows($from, $to) as $row) {
            $this->addUsage(
                $categories[self::CATEGORY_WORKOUT_GENERATION],
                status: $this->statusFromUsageRow($row),
                usage: [
                    'model' => $row['model'] ?? null,
                    'prompt_tokens' => $row['promptTokens'] ?? null,
                    'completion_tokens' => $row['completionTokens'] ?? null,
                    'total_tokens' => $row['totalTokens'] ?? null,
                    'estimated_cost_usd' => $row['estimatedCostUsd'] ?? null,
                ],
            );
        }

        foreach ($this->analysisRows($from, $to) as $row) {
            $this->addUsage(
                $categories[self::CATEGORY_ATHLETE_ANALYSIS],
                status: $this->statusValue($row['status'] ?? null),
                usage: $this->usageFromPayload($row['result'] ?? null),
            );
        }

        foreach ($this->programmingRows($from, $to) as $row) {
            $category = match ($this->statusValue($row['type'] ?? null)) {
                ProgrammingGenerationTypeEnum::BOX->value => self::CATEGORY_BOX_PROGRAMMING,
                ProgrammingGenerationTypeEnum::COMPETITION->value => self::CATEGORY_COMPETITION_PROGRAMMING,
                default => self::CATEGORY_ATHLETE_PROGRAMMING,
            };

            $this->addUsage(
                $categories[$category],
                status: $this->statusValue($row['status'] ?? null),
                usage: $this->usageFromPayload($row['generatedProgramming'] ?? null),
            );
        }

        foreach ($this->programmingSessionDetailRows($from, $to) as $row) {
            $category = match ($this->statusValue($row['type'] ?? null)) {
                ProgrammingGenerationTypeEnum::BOX->value => self::CATEGORY_BOX_PROGRAMMING,
                ProgrammingGenerationTypeEnum::COMPETITION->value => self::CATEGORY_COMPETITION_PROGRAMMING,
                default => self::CATEGORY_ATHLETE_PROGRAMMING,
            };

            $this->addUsage(
                $categories[$category],
                status: $this->statusValue($row['status'] ?? null),
                usage: $this->usageFromPayload($row['detailedProgramming'] ?? null),
            );
        }

        $finalCategories = [];
        foreach ($categories as $key => $category) {
            $finalCategories[$key] = $this->finalizeCategory($category);
        }

        return [
            'generatedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'period' => [
                'from' => $from->format(\DateTimeInterface::ATOM),
                'to' => $to->format(\DateTimeInterface::ATOM),
            ],
            'totals' => $this->globalTotals($finalCategories),
            'categories' => $finalCategories,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function emptyCategories(): array
    {
        return [
            self::CATEGORY_WORKOUT_GENERATION => $this->emptyAccumulator('Generation de WOD'),
            self::CATEGORY_ATHLETE_ANALYSIS => $this->emptyAccumulator('Analyse IA athlete'),
            self::CATEGORY_ATHLETE_PROGRAMMING => $this->emptyAccumulator('Programmation athlete'),
            self::CATEGORY_BOX_PROGRAMMING => $this->emptyAccumulator('Programmation box'),
            self::CATEGORY_COMPETITION_PROGRAMMING => $this->emptyAccumulator('Programmation competition'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyAccumulator(string $label): array
    {
        return [
            'label' => $label,
            'successfulCount' => 0,
            'failureCount' => 0,
            'promptTokens' => 0,
            'completionTokens' => 0,
            'totalTokens' => 0,
            'knownCostMicros' => 0,
            'failureKnownCostMicros' => 0,
            'failureTokenKnownCostMicros' => 0,
            'successfulKnownCostMicros' => 0,
            'knownCostCount' => 0,
            'unknownCostCount' => 0,
            'successfulKnownCostCount' => 0,
            'successfulUnknownCostCount' => 0,
            'failureKnownCostCount' => 0,
            'failureTokenKnownCostCount' => 0,
            'failureUnknownCostCount' => 0,
            'models' => [],
            'byModel' => [],
        ];
    }

    /**
     * @param array<string, mixed>      $category
     * @param array<string, mixed>|null $usage
     */
    private function addUsage(array &$category, string $status, ?array $usage): void
    {
        $isSuccess = $this->isSuccessStatus($status);
        $isFailure = $this->isFailureStatus($status);

        if (!$isSuccess && !$isFailure) {
            return;
        }

        if ($isSuccess) {
            ++$category['successfulCount'];
        } else {
            ++$category['failureCount'];
        }

        $model = $this->modelFromUsage($usage);
        $category['models'][$model] = true;
        if (!isset($category['byModel'][$model])) {
            $category['byModel'][$model] = $this->emptyAccumulator($model);
        }

        $this->addUsageToBucket($category, $usage, $isSuccess, $isFailure);
        $this->addUsageToBucket($category['byModel'][$model], $usage, $isSuccess, $isFailure);

        if ($isSuccess) {
            ++$category['byModel'][$model]['successfulCount'];
        } else {
            ++$category['byModel'][$model]['failureCount'];
        }
        $category['byModel'][$model]['models'][$model] = true;
    }

    /**
     * @param array<string, mixed>      $bucket
     * @param array<string, mixed>|null $usage
     */
    private function addUsageToBucket(array &$bucket, ?array $usage, bool $isSuccess, bool $isFailure): void
    {
        $bucket['promptTokens'] += $this->nullableInt($usage['prompt_tokens'] ?? null) ?? 0;
        $bucket['completionTokens'] += $this->nullableInt($usage['completion_tokens'] ?? null) ?? 0;
        $totalTokens = $this->nullableInt($usage['total_tokens'] ?? null) ?? 0;
        $bucket['totalTokens'] += $totalTokens;

        $costMicros = $this->decimalToMicros($usage['estimated_cost_usd'] ?? null);
        if ($costMicros === null) {
            ++$bucket['unknownCostCount'];
            if ($isSuccess) {
                ++$bucket['successfulUnknownCostCount'];
            }
            if ($isFailure) {
                ++$bucket['failureUnknownCostCount'];
            }

            return;
        }

        ++$bucket['knownCostCount'];
        $bucket['knownCostMicros'] += $costMicros;
        if ($isSuccess) {
            ++$bucket['successfulKnownCostCount'];
            $bucket['successfulKnownCostMicros'] += $costMicros;
        }
        if ($isFailure) {
            ++$bucket['failureKnownCostCount'];
            $bucket['failureKnownCostMicros'] += $costMicros;
            if ($totalTokens > 0) {
                ++$bucket['failureTokenKnownCostCount'];
                $bucket['failureTokenKnownCostMicros'] += $costMicros;
            }
        }
    }

    /**
     * @param array<string, mixed> $category
     *
     * @return array<string, mixed>
     */
    private function finalizeCategory(array $category): array
    {
        $models = array_keys($category['models']);
        sort($models);

        $byModel = [];
        foreach ($category['byModel'] as $model => $modelCategory) {
            $byModel[$model] = $this->finalizeCategory($modelCategory);
        }
        ksort($byModel);

        return [
            'label' => $category['label'],
            'successfulCount' => $category['successfulCount'],
            'failureCount' => $category['failureCount'],
            'averageSuccessfulEstimatedCostUsd' => $this->averageMicros(
                $category['successfulKnownCostMicros'],
                $category['successfulKnownCostCount'],
            ),
            'totalEstimatedCostUsd' => $this->microsToDecimal($category['knownCostMicros'], $category['knownCostCount']),
            'failedEstimatedCostUsd' => $this->microsToDecimal($category['failureKnownCostMicros'], $category['failureKnownCostCount']),
            'failedWithTokensEstimatedCostUsd' => $this->microsToDecimal(
                $category['failureTokenKnownCostMicros'],
                $category['failureTokenKnownCostCount'],
            ),
            'knownCostCount' => $category['knownCostCount'],
            'unknownCostCount' => $category['unknownCostCount'],
            'successfulKnownCostCount' => $category['successfulKnownCostCount'],
            'successfulUnknownCostCount' => $category['successfulUnknownCostCount'],
            'failureKnownCostCount' => $category['failureKnownCostCount'],
            'failureTokenKnownCostCount' => $category['failureTokenKnownCostCount'],
            'failureUnknownCostCount' => $category['failureUnknownCostCount'],
            'tokens' => [
                'prompt' => $category['promptTokens'],
                'completion' => $category['completionTokens'],
                'total' => $category['totalTokens'],
            ],
            'models' => $models,
            'byModel' => $byModel,
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $categories
     *
     * @return array<string, mixed>
     */
    private function globalTotals(array $categories): array
    {
        $total = [
            'successfulCount' => 0,
            'failureCount' => 0,
            'promptTokens' => 0,
            'completionTokens' => 0,
            'totalTokens' => 0,
            'knownCostMicros' => 0,
            'failureTokenKnownCostMicros' => 0,
            'knownCostCount' => 0,
            'unknownCostCount' => 0,
            'byModel' => [],
        ];

        foreach ($categories as $category) {
            $total['successfulCount'] += $category['successfulCount'];
            $total['failureCount'] += $category['failureCount'];
            $total['promptTokens'] += $category['tokens']['prompt'];
            $total['completionTokens'] += $category['tokens']['completion'];
            $total['totalTokens'] += $category['tokens']['total'];
            $total['knownCostMicros'] += $this->decimalToMicros($category['totalEstimatedCostUsd']) ?? 0;
            $total['failureTokenKnownCostMicros'] += $this->decimalToMicros($category['failedWithTokensEstimatedCostUsd']) ?? 0;
            $total['knownCostCount'] += $category['knownCostCount'];
            $total['unknownCostCount'] += $category['unknownCostCount'];
            foreach ($category['byModel'] as $model => $modelCategory) {
                if (!isset($total['byModel'][$model])) {
                    $total['byModel'][$model] = $this->emptyFinalizedModelAccumulator($model);
                }
                $this->mergeFinalizedMetrics($total['byModel'][$model], $modelCategory);
            }
        }

        $byModel = [];
        foreach ($total['byModel'] as $model => $modelAccumulator) {
            $byModel[$model] = $this->finalizeMetricsAccumulator($modelAccumulator);
        }
        ksort($byModel);

        return [
            'successfulCount' => $total['successfulCount'],
            'failureCount' => $total['failureCount'],
            'totalEstimatedCostUsd' => $this->microsToDecimal($total['knownCostMicros'], $total['knownCostCount']),
            'failedWithTokensEstimatedCostUsd' => $this->microsToDecimal(
                $total['failureTokenKnownCostMicros'],
                $this->sumIntValues($categories, 'failureTokenKnownCostCount'),
            ),
            'knownCostCount' => $total['knownCostCount'],
            'unknownCostCount' => $total['unknownCostCount'],
            'tokens' => [
                'prompt' => $total['promptTokens'],
                'completion' => $total['completionTokens'],
                'total' => $total['totalTokens'],
            ],
            'byModel' => $byModel,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyFinalizedModelAccumulator(string $label): array
    {
        return [
            'label' => $label,
            'successfulCount' => 0,
            'failureCount' => 0,
            'promptTokens' => 0,
            'completionTokens' => 0,
            'totalTokens' => 0,
            'knownCostMicros' => 0,
            'failureTokenKnownCostMicros' => 0,
            'successfulKnownCostMicros' => 0,
            'knownCostCount' => 0,
            'unknownCostCount' => 0,
            'successfulKnownCostCount' => 0,
            'successfulUnknownCostCount' => 0,
            'failureKnownCostCount' => 0,
            'failureTokenKnownCostCount' => 0,
            'failureUnknownCostCount' => 0,
        ];
    }

    /**
     * @param array<string, mixed> $accumulator
     * @param array<string, mixed> $metrics
     */
    private function mergeFinalizedMetrics(array &$accumulator, array $metrics): void
    {
        $successfulKnownCount = $this->nullableInt($metrics['successfulKnownCostCount'] ?? null) ?? 0;

        $accumulator['successfulCount'] += $this->nullableInt($metrics['successfulCount'] ?? null) ?? 0;
        $accumulator['failureCount'] += $this->nullableInt($metrics['failureCount'] ?? null) ?? 0;
        $accumulator['promptTokens'] += $this->nullableInt($metrics['tokens']['prompt'] ?? null) ?? 0;
        $accumulator['completionTokens'] += $this->nullableInt($metrics['tokens']['completion'] ?? null) ?? 0;
        $accumulator['totalTokens'] += $this->nullableInt($metrics['tokens']['total'] ?? null) ?? 0;
        $accumulator['knownCostMicros'] += $this->decimalToMicros($metrics['totalEstimatedCostUsd'] ?? null) ?? 0;
        $accumulator['failureTokenKnownCostMicros'] += $this->decimalToMicros($metrics['failedWithTokensEstimatedCostUsd'] ?? null) ?? 0;
        $accumulator['successfulKnownCostMicros'] += ($this->decimalToMicros($metrics['averageSuccessfulEstimatedCostUsd'] ?? null) ?? 0) * $successfulKnownCount;
        $accumulator['knownCostCount'] += $this->nullableInt($metrics['knownCostCount'] ?? null) ?? 0;
        $accumulator['unknownCostCount'] += $this->nullableInt($metrics['unknownCostCount'] ?? null) ?? 0;
        $accumulator['successfulKnownCostCount'] += $successfulKnownCount;
        $accumulator['successfulUnknownCostCount'] += $this->nullableInt($metrics['successfulUnknownCostCount'] ?? null) ?? 0;
        $accumulator['failureKnownCostCount'] += $this->nullableInt($metrics['failureKnownCostCount'] ?? null) ?? 0;
        $accumulator['failureTokenKnownCostCount'] += $this->nullableInt($metrics['failureTokenKnownCostCount'] ?? null) ?? 0;
        $accumulator['failureUnknownCostCount'] += $this->nullableInt($metrics['failureUnknownCostCount'] ?? null) ?? 0;
    }

    /**
     * @param array<string, mixed> $accumulator
     *
     * @return array<string, mixed>
     */
    private function finalizeMetricsAccumulator(array $accumulator): array
    {
        return [
            'label' => $accumulator['label'],
            'successfulCount' => $accumulator['successfulCount'],
            'failureCount' => $accumulator['failureCount'],
            'averageSuccessfulEstimatedCostUsd' => $this->averageMicros(
                $accumulator['successfulKnownCostMicros'],
                $accumulator['successfulKnownCostCount'],
            ),
            'totalEstimatedCostUsd' => $this->microsToDecimal($accumulator['knownCostMicros'], $accumulator['knownCostCount']),
            'failedWithTokensEstimatedCostUsd' => $this->microsToDecimal(
                $accumulator['failureTokenKnownCostMicros'],
                $accumulator['failureTokenKnownCostCount'],
            ),
            'knownCostCount' => $accumulator['knownCostCount'],
            'unknownCostCount' => $accumulator['unknownCostCount'],
            'successfulKnownCostCount' => $accumulator['successfulKnownCostCount'],
            'successfulUnknownCostCount' => $accumulator['successfulUnknownCostCount'],
            'failureKnownCostCount' => $accumulator['failureKnownCostCount'],
            'failureTokenKnownCostCount' => $accumulator['failureTokenKnownCostCount'],
            'failureUnknownCostCount' => $accumulator['failureUnknownCostCount'],
            'tokens' => [
                'prompt' => $accumulator['promptTokens'],
                'completion' => $accumulator['completionTokens'],
                'total' => $accumulator['totalTokens'],
            ],
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $categories
     */
    private function sumIntValues(array $categories, string $key): int
    {
        return array_sum(array_map(
            fn (array $category): int => $this->nullableInt($category[$key] ?? null) ?? 0,
            $categories,
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function workoutUsageRows(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select(
                'usage.status',
                'usage.model',
                'usage.promptTokens',
                'usage.completionTokens',
                'usage.totalTokens',
                'usage.estimatedCostUsd',
            )
            ->from(WorkoutAiGenerationUsage::class, 'usage')
            ->andWhere('usage.createdAt >= :from')
            ->andWhere('usage.createdAt < :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function analysisRows(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('request.status', 'request.result', 'request.completedAt')
            ->from(PerformanceAnalysisRequest::class, 'request')
            ->andWhere('request.status IN (:statuses)')
            ->andWhere('request.completedAt >= :from')
            ->andWhere('request.completedAt < :to')
            ->setParameter('statuses', [
                AnalysisRequestStatusEnum::COMPLETED,
                AnalysisRequestStatusEnum::FAILED,
            ])
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function programmingRows(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('request.status', 'request.type', 'request.generatedProgramming', 'request.completedAt')
            ->from(ProgrammingGenerationRequest::class, 'request')
            ->andWhere('request.status IN (:statuses)')
            ->andWhere('request.completedAt >= :from')
            ->andWhere('request.completedAt < :to')
            ->setParameter('statuses', [
                ProgrammingGenerationRequestStatusEnum::COMPLETED,
                ProgrammingGenerationRequestStatusEnum::FAILED,
            ])
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function programmingSessionDetailRows(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->entityManager
            ->createQueryBuilder()
            ->select('request.status', 'request.detailedProgramming', 'request.completedAt', 'programmingRequest.type')
            ->from(ProgrammingSessionDetailRequest::class, 'request')
            ->innerJoin('request.programmingRequest', 'programmingRequest')
            ->andWhere('request.status IN (:statuses)')
            ->andWhere('request.completedAt >= :from')
            ->andWhere('request.completedAt < :to')
            ->setParameter('statuses', [
                ProgrammingGenerationRequestStatusEnum::COMPLETED,
                ProgrammingGenerationRequestStatusEnum::FAILED,
            ])
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @param array<string, mixed>|null $payload
     *
     * @return array<string, mixed>|null
     */
    private function usageFromPayload(mixed $payload): ?array
    {
        if (!is_array($payload) || !is_array($payload['_openai_usage'] ?? null)) {
            return null;
        }

        return $payload['_openai_usage'];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function statusFromUsageRow(array $row): string
    {
        $status = $row['status'] ?? null;

        return is_string($status) ? $status : '';
    }

    private function statusValue(mixed $status): string
    {
        if ($status instanceof \BackedEnum) {
            return (string) $status->value;
        }

        return is_string($status) ? $status : '';
    }

    /**
     * @param array<string, mixed>|null $usage
     */
    private function modelFromUsage(?array $usage): string
    {
        $model = $usage['model'] ?? null;

        return is_string($model) && trim($model) !== '' ? trim($model) : 'unknown';
    }

    private function isSuccessStatus(string $status): bool
    {
        return in_array($status, ['success', 'completed'], true);
    }

    private function isFailureStatus(string $status): bool
    {
        return in_array($status, ['failure', 'failed'], true);
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

    private function decimalToMicros(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value * 1_000_000;
        }
        if (is_float($value)) {
            return $value >= 0 ? (int) round($value * 1_000_000) : null;
        }
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        if (preg_match('/^\d+(?:\.\d+)?$/', $value) !== 1) {
            if (!is_numeric($value) || str_starts_with($value, '-')) {
                return null;
            }

            return (int) round((float) $value * 1_000_000);
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $whole * 1_000_000) + (int) substr(str_pad($fraction, 6, '0'), 0, 6);
    }

    private function microsToDecimal(int $micros, int $knownCount): ?string
    {
        if ($knownCount === 0) {
            return null;
        }

        return sprintf('%.6F', $micros / 1_000_000);
    }

    private function averageMicros(int $micros, int $count): ?string
    {
        if ($count === 0) {
            return null;
        }

        return sprintf('%.6F', ($micros / $count) / 1_000_000);
    }
}
