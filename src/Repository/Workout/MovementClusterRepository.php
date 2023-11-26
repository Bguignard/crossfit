<?php

namespace App\Repository\Workout;

use App\Entity\Workout\MovementCluster;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MovementCluster>
 *
 * @method MovementCluster|null find($id, $lockMode = null, $lockVersion = null)
 * @method MovementCluster|null findOneBy(array $criteria, array $orderBy = null)
 * @method MovementCluster[]    findAll()
 * @method MovementCluster[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovementClusterRepository extends ServiceEntityRepository implements MovementClusterRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MovementCluster::class);
    }
}
