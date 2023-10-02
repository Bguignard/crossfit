<?php

namespace App\Workout\Repository;

use App\Workout\Entity\MovementCluster;
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
class MovementClusterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MovementCluster::class);
    }

//    /**
//     * @return MovementCluster[] Returns an array of MovementCluster objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?MovementCluster
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
