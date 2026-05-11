<?php

namespace App\Services\Admin;

use App\Entity\Competition\Athlete;
use App\Entity\Product\Box;
use App\Entity\Product\BoxMembership;
use App\Entity\Product\PerformanceAnalysisRequest;
use App\Entity\Product\ProgrammingGenerationRequest;
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
     *     workouts: array{total: int, by_source: array<string, int>},
     *     athletes: array{total: int, by_source: array<string, int>},
     *     users: array{total: int},
     *     linked_athlete_profiles: array{total: int},
     *     performance_profiles: array{total: int},
     *     analysis_requests: array{total: int, by_status: array<string, int>},
     *     programming_requests: array{total: int, by_status: array<string, int>, by_type: array<string, int>},
     *     boxes: array{total: int},
     *     box_memberships: array{total: int}
     * }
     */
    public function getMetrics(): array
    {
        return [
            'workouts' => [
                'total' => $this->count(Workout::class),
                'by_source' => $this->countByNullableField(Workout::class, 'sourceName', 'manual'),
            ],
            'athletes' => [
                'total' => $this->count(Athlete::class),
                'by_source' => $this->countByField(Athlete::class, 'sourceName'),
            ],
            'users' => [
                'total' => $this->count(User::class),
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
