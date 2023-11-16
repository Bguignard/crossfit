<?php

namespace App\Repository\Workout;

use App\Entity\Workout\WorkoutOriginName;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkoutOriginName>
 *
 * @method WorkoutOriginName|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkoutOriginName|null findOneBy(array $criteria, array $orderBy = null)
 * @method WorkoutOriginName[]    findAll()
 * @method WorkoutOriginName[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkoutOriginNameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutOriginName::class);
    }
}
