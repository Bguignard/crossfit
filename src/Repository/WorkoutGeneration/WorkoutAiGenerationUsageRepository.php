<?php

namespace App\Repository\WorkoutGeneration;

use App\Entity\Security\User;
use App\Entity\WorkoutGeneration\WorkoutAiGenerationUsage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkoutAiGenerationUsage>
 */
class WorkoutAiGenerationUsageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutAiGenerationUsage::class);
    }

    public function countQuotaUsage(?User $user, ?string $visitorHash, \DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        $queryBuilder = $this->createQueryBuilder('usage')
            ->select('COUNT(usage.id)')
            ->andWhere('usage.quotaCounted = true')
            ->andWhere('usage.createdAt >= :from')
            ->andWhere('usage.createdAt < :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to);

        if ($user instanceof User) {
            $queryBuilder
                ->andWhere('usage.user = :user')
                ->setParameter('user', $user);
        } else {
            $queryBuilder
                ->andWhere('usage.user IS NULL')
                ->andWhere('usage.visitorHash = :visitorHash')
                ->setParameter('visitorHash', $visitorHash);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
