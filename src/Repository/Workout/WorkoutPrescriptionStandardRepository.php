<?php

namespace App\Repository\Workout;

use App\Entity\Workout\WorkoutPrescriptionStandard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkoutPrescriptionStandard>
 *
 * @method WorkoutPrescriptionStandard|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkoutPrescriptionStandard|null findOneBy(array $criteria, array $orderBy = null)
 * @method WorkoutPrescriptionStandard[]    findAll()
 * @method WorkoutPrescriptionStandard[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkoutPrescriptionStandardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutPrescriptionStandard::class);
    }

    /**
     * @param list<string> $movementNames
     * @param list<string> $implementNames
     *
     * @return list<WorkoutPrescriptionStandard>
     */
    public function findForPrompt(
        string $levelName,
        array $movementNames,
        array $implementNames,
        bool $includeHyrox,
        int $limit = 30,
    ): array {
        $query = $this->createQueryBuilder('standard')
            ->where('(standard.levelName = :levelName OR standard.levelName IS NULL)')
            ->andWhere($includeHyrox ? 'standard.sport IN (:sports)' : 'standard.sport = :sport')
            ->setParameter('levelName', $levelName)
            ->orderBy('standard.priority', 'ASC')
            ->addOrderBy('standard.sport', 'ASC')
            ->addOrderBy('standard.implementName', 'ASC')
            ->addOrderBy('standard.movementName', 'ASC')
            ->addOrderBy('standard.division', 'ASC')
            ->setMaxResults($limit);

        if ($includeHyrox) {
            $query->setParameter('sports', ['crossfit', 'hyrox']);
        } else {
            $query->setParameter('sport', 'crossfit');
        }

        $orExpressions = ['standard.movementName IS NULL AND standard.implementName IS NULL'];
        if ($movementNames !== []) {
            $orExpressions[] = 'standard.movementName IN (:movementNames)';
            $query->setParameter('movementNames', $movementNames);
        }
        if ($implementNames !== []) {
            $orExpressions[] = 'standard.implementName IN (:implementNames)';
            $query->setParameter('implementNames', $implementNames);
        }
        if ($includeHyrox) {
            $orExpressions[] = 'standard.sport = :hyroxSport';
            $query->setParameter('hyroxSport', 'hyrox');
        }

        return $query
            ->andWhere('('.implode(' OR ', $orExpressions).')')
            ->getQuery()
            ->getResult();
    }
}
