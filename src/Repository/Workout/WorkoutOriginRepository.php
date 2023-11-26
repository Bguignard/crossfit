<?php

namespace App\Repository\Workout;

use App\Entity\Workout\WorkoutOrigin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkoutOrigin>
 *
 * @method WorkoutOrigin|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkoutOrigin|null findOneBy(array $criteria, array $orderBy = null)
 * @method WorkoutOrigin[]    findAll()
 * @method WorkoutOrigin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkoutOriginRepository extends ServiceEntityRepository implements WorkoutOriginRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutOrigin::class);
    }
}
