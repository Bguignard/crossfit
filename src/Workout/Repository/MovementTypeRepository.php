<?php

namespace App\Workout\Repository;

use App\Workout\Entity\RepUnit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RepUnit>
 *
 * @method RepUnit|null find($id, $lockMode = null, $lockVersion = null)
 * @method RepUnit|null findOneBy(array $criteria, array $orderBy = null)
 * @method RepUnit[]    findAll()
 * @method RepUnit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovementTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RepUnit::class);
    }

//    /**
//     * @return MovementClusterType[] Returns an array of MovementClusterType objects
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

//    public function findOneBySomeField($value): ?MovementClusterType
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
