<?php

namespace App\Repository;

use App\Entities\Workout\MovementImplement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MovementImplement>
 *
 * @method MovementImplement|null find($id, $lockMode = null, $lockVersion = null)
 * @method MovementImplement|null findOneBy(array $criteria, array $orderBy = null)
 * @method MovementImplement[]    findAll()
 * @method MovementImplement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovementImplementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MovementImplement::class);
    }

//    /**
//     * @return MovementImplement[] Returns an array of MovementImplement objects
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

//    public function findOneBySomeField($value): ?MovementImplement
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
