<?php

namespace App\Services\Admin;

use App\Entity\Competition\Athlete;
use App\Entity\Competition\Competition;
use App\Entity\Competition\CompetitionDivision;
use App\Entity\Competition\CompetitionEvent;
use App\Entity\Competition\WorkoutResult;
use App\Entity\Product\Box;
use App\Entity\Product\BoxMembership;
use App\Entity\Product\PerformanceAnalysisRequest;
use App\Entity\Product\ProgrammingGenerationRequest;
use App\Entity\Product\ProgrammingSessionDetailRequest;
use App\Entity\Product\UserAthleteProfile;
use App\Entity\Product\UserPerformanceProfile;
use App\Entity\Security\User;
use App\Entity\Workout\Workout;
use Doctrine\ORM\EntityManagerInterface;

class AdminDashboardMetricsProvider
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @return array{
     *     generated_at: string,
     *     workouts: array{total: int, by_source: array<string, int>, by_type: array<string, int>, by_origin: array<string, int>, latest_created_at: ?string},
     *     athletes: array{total: int, by_source: array<string, int>, latest_updated_at: ?string},
     *     competitions: array{total: int, by_source: array<string, int>, latest_updated_at: ?string},
     *     competition_events: array{total: int, by_source: array<string, int>, latest_updated_at: ?string},
     *     competition_divisions: array{total: int, by_source: array<string, int>, latest_updated_at: ?string},
     *     workout_results: array{total: int, by_source: array<string, int>, latest_updated_at: ?string},
     *     users: array{total: int, verified: int, admins: int, latest_created_at: ?string},
     *     linked_athlete_profiles: array{total: int},
     *     performance_profiles: array{total: int},
     *     analysis_requests: array{total: int, by_status: array<string, int>},
     *     programming_requests: array{total: int, by_status: array<string, int>, by_type: array<string, int>},
     *     ai_usage: array{total_tokens: int, prompt_tokens: int, completion_tokens: int, by_request_type: array<string, array{total_tokens: int, prompt_tokens: int, completion_tokens: int, calls: int}>},
     *     boxes: array{total: int},
     *     box_memberships: array{total: int}
     * }
     */
    public function getMetrics(): array
    {
        return [
            'generated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'workouts' => [
                'total' => $this->count(Workout::class),
                'by_source' => $this->countByNullableField(Workout::class, 'sourceName', 'manual'),
                'by_type' => $this->countByJoinedField(Workout::class, 'workoutType', 'name', 'unknown'),
                'by_origin' => $this->countByJoinedPath(Workout::class, ['workoutOrigin', 'name'], 'name', 'unknown'),
                'latest_created_at' => $this->latestDate(Workout::class, 'createdAt'),
            ],
            'athletes' => [
                'total' => $this->count(Athlete::class),
                'by_source' => $this->countByField(Athlete::class, 'sourceName'),
                'latest_updated_at' => $this->latestDate(Athlete::class, 'updatedAt'),
            ],
            'competitions' => [
                'total' => $this->count(Competition::class),
                'by_source' => $this->countByField(Competition::class, 'sourceName'),
                'latest_updated_at' => $this->latestDate(Competition::class, 'updatedAt'),
            ],
            'competition_events' => [
                'total' => $this->count(CompetitionEvent::class),
                'by_source' => $this->countByField(CompetitionEvent::class, 'sourceName'),
                'latest_updated_at' => $this->latestDate(CompetitionEvent::class, 'updatedAt'),
            ],
            'competition_divisions' => [
                'total' => $this->count(CompetitionDivision::class),
                'by_source' => $this->countByField(CompetitionDivision::class, 'sourceName'),
                'latest_updated_at' => $this->latestDate(CompetitionDivision::class, 'updatedAt'),
            ],
            'workout_results' => [
                'total' => $this->count(WorkoutResult::class),
                'by_source' => $this->countByField(WorkoutResult::class, 'sourceName'),
                'latest_updated_at' => $this->latestDate(WorkoutResult::class, 'updatedAt'),
            ],
            'users' => [
                'total' => $this->count(User::class),
                'verified' => $this->countWhereNotNull(User::class, 'emailVerifiedAt'),
                'admins' => $this->countUsersWithRole('ROLE_ADMIN'),
                'latest_created_at' => $this->latestDate(User::class, 'createdAt'),
            ],
            'linked_athlete_profiles' => [
                'total' => $this->count(UserAthleteProfile::class),
            ],
            'performance_profiles' => [
                'total' => $this->count(UserPerformanceProfile::class),
            ],
            'analysis_requests' => [
                'total' => $this->count(PerformanceAnalysisRequest::class),
                'by_status' => $this->countByField(PerformanceAnalysisRequest::class, 'status'),
            ],
            'programming_requests' => [
                'total' => $this->count(ProgrammingGenerationRequest::class),
                'by_status' => $this->countByField(ProgrammingGenerationRequest::class, 'status'),
                'by_type' => $this->countByField(ProgrammingGenerationRequest::class, 'type'),
            ],
            'ai_usage' => $this->aiUsageMetrics(),
            'boxes' => [
                'total' => $this->count(Box::class),
            ],
            'box_memberships' => [
                'total' => $this->count(BoxMembership::class),
            ],
        ];
    }

    /**
     * @param class-string $entityClass
     */
    private function count(string $entityClass): int
    {
        return (int) $this->entityManager
            ->createQueryBuilder()
            ->select('COUNT(entity.id)')
            ->from($entityClass, 'entity')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array{total_tokens: int, prompt_tokens: int, completion_tokens: int, by_request_type: array<string, array{total_tokens: int, prompt_tokens: int, completion_tokens: int, calls: int}>}
     */
    private function aiUsageMetrics(): array
    {
        $byRequestType = [
            'analysis' => $this->sumUsageForPayloads(
                array_map(
                    static fn (PerformanceAnalysisRequest $request): ?array => $request->getResult(),
                    $this->entityManager->getRepository(PerformanceAnalysisRequest::class)->findAll(),
                ),
            ),
            'programming' => $this->sumUsageForPayloads(
                array_map(
                    static fn (ProgrammingGenerationRequest $request): ?array => $request->getGeneratedProgramming(),
                    $this->entityManager->getRepository(ProgrammingGenerationRequest::class)->findAll(),
                ),
            ),
            'programming_session_details' => $this->sumUsageForPayloads(
                array_map(
                    static fn (ProgrammingSessionDetailRequest $request): ?array => $request->getDetailedProgramming(),
                    $this->entityManager->getRepository(ProgrammingSessionDetailRequest::class)->findAll(),
                ),
            ),
            'workout_generation' => $this->sumUsagePayloads(
                array_map(
                    static fn (Workout $workout): ?array => $workout->getAiUsage(),
                    $this->entityManager->getRepository(Workout::class)->findAll(),
                ),
            ),
        ];

        return [
            'total_tokens' => array_sum(array_column($byRequestType, 'total_tokens')),
            'prompt_tokens' => array_sum(array_column($byRequestType, 'prompt_tokens')),
            'completion_tokens' => array_sum(array_column($byRequestType, 'completion_tokens')),
            'by_request_type' => $byRequestType,
        ];
    }

    /**
     * @param list<array<string, mixed>|null> $payloads
     *
     * @return array{total_tokens: int, prompt_tokens: int, completion_tokens: int, calls: int}
     */
    private function sumUsageForPayloads(array $payloads): array
    {
        return $this->sumUsagePayloads(array_map(
            static fn (?array $payload): ?array => is_array($payload['_openai_usage'] ?? null) ? $payload['_openai_usage'] : null,
            $payloads,
        ));
    }

    /**
     * @param list<array<string, mixed>|null> $payloads
     *
     * @return array{total_tokens: int, prompt_tokens: int, completion_tokens: int, calls: int}
     */
    private function sumUsagePayloads(array $payloads): array
    {
        $summary = [
            'total_tokens' => 0,
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'calls' => 0,
        ];

        foreach ($payloads as $usage) {
            if ($usage === null) {
                continue;
            }

            ++$summary['calls'];
            $summary['total_tokens'] += $this->intUsageValue($usage['total_tokens'] ?? null);
            $summary['prompt_tokens'] += $this->intUsageValue($usage['prompt_tokens'] ?? null);
            $summary['completion_tokens'] += $this->intUsageValue($usage['completion_tokens'] ?? null);
        }

        return $summary;
    }

    private function intUsageValue(mixed $value): int
    {
        return is_int($value) ? $value : 0;
    }

    /**
     * @param class-string $entityClass
     *
     * @return array<string, int>
     */
    private function countByField(string $entityClass, string $field): array
    {
        $rows = $this->entityManager
            ->createQueryBuilder()
            ->select(sprintf('entity.%s AS metric_key', $field), 'COUNT(entity.id) AS metric_count')
            ->from($entityClass, 'entity')
            ->groupBy(sprintf('entity.%s', $field))
            ->orderBy('metric_key', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return $this->normalizeGroupedRows($rows);
    }

    /**
     * @param class-string $entityClass
     *
     * @return array<string, int>
     */
    private function countByNullableField(string $entityClass, string $field, string $fallback): array
    {
        $rows = $this->entityManager
            ->createQueryBuilder()
            ->select(sprintf('COALESCE(entity.%s, :fallback) AS metric_key', $field), 'COUNT(entity.id) AS metric_count')
            ->from($entityClass, 'entity')
            ->groupBy('metric_key')
            ->orderBy('metric_key', 'ASC')
            ->setParameter('fallback', $fallback)
            ->getQuery()
            ->getArrayResult();

        return $this->normalizeGroupedRows($rows);
    }

    /**
     * @param class-string $entityClass
     *
     * @return array<string, int>
     */
    private function countByJoinedField(string $entityClass, string $association, string $field, string $fallback): array
    {
        $rows = $this->entityManager
            ->createQueryBuilder()
            ->select(sprintf('COALESCE(joined.%s, :fallback) AS metric_key', $field), 'COUNT(entity.id) AS metric_count')
            ->from($entityClass, 'entity')
            ->leftJoin(sprintf('entity.%s', $association), 'joined')
            ->groupBy('metric_key')
            ->orderBy('metric_key', 'ASC')
            ->setParameter('fallback', $fallback)
            ->getQuery()
            ->getArrayResult();

        return $this->normalizeGroupedRows($rows);
    }

    /**
     * @param class-string $entityClass
     * @param list<string> $associations
     *
     * @return array<string, int>
     */
    private function countByJoinedPath(string $entityClass, array $associations, string $field, string $fallback): array
    {
        $queryBuilder = $this->entityManager
            ->createQueryBuilder()
            ->from($entityClass, 'entity');

        $previousAlias = 'entity';
        foreach ($associations as $index => $association) {
            $alias = sprintf('joined_%d', $index);
            $queryBuilder->leftJoin(sprintf('%s.%s', $previousAlias, $association), $alias);
            $previousAlias = $alias;
        }

        $rows = $queryBuilder
            ->select(sprintf('COALESCE(%s.%s, :fallback) AS metric_key', $previousAlias, $field), 'COUNT(entity.id) AS metric_count')
            ->groupBy('metric_key')
            ->orderBy('metric_key', 'ASC')
            ->setParameter('fallback', $fallback)
            ->getQuery()
            ->getArrayResult();

        return $this->normalizeGroupedRows($rows);
    }

    /**
     * @param class-string $entityClass
     */
    private function countWhereNotNull(string $entityClass, string $field): int
    {
        return (int) $this->entityManager
            ->createQueryBuilder()
            ->select('COUNT(entity.id)')
            ->from($entityClass, 'entity')
            ->where(sprintf('entity.%s IS NOT NULL', $field))
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function countUsersWithRole(string $role): int
    {
        $users = $this->entityManager
            ->createQueryBuilder()
            ->select('entity.roles')
            ->from(User::class, 'entity')
            ->getQuery()
            ->getArrayResult();

        return count(array_filter(
            $users,
            static fn (array $row): bool => in_array($role, $row['roles'] ?? [], true)
        ));
    }

    /**
     * @param class-string $entityClass
     */
    private function latestDate(string $entityClass, string $field): ?string
    {
        $latest = $this->entityManager
            ->createQueryBuilder()
            ->select(sprintf('MAX(entity.%s)', $field))
            ->from($entityClass, 'entity')
            ->getQuery()
            ->getSingleScalarResult();

        if ($latest instanceof \DateTimeInterface) {
            return $latest->format(\DateTimeInterface::ATOM);
        }

        if (is_string($latest) && $latest !== '') {
            return (new \DateTimeImmutable($latest))->format(\DateTimeInterface::ATOM);
        }

        return null;
    }

    /**
     * @param list<array{metric_key: mixed, metric_count: numeric-string|int}> $rows
     *
     * @return array<string, int>
     */
    private function normalizeGroupedRows(array $rows): array
    {
        $metrics = [];

        foreach ($rows as $row) {
            $key = $row['metric_key'];
            if ($key instanceof \BackedEnum) {
                $key = $key->value;
            }

            $metrics[(string) $key] = (int) $row['metric_count'];
        }

        return $metrics;
    }
}
