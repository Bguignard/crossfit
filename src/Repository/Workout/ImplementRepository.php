<?php

namespace App\Repository\Workout;

use App\Entity\Workout\Implement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Implement>
 *
 * @method Implement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Implement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Implement[]    findAll()
 * @method Implement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImplementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Implement::class);
    }

    //    /**
    //     * @return BodyPart[] Returns an array of BodyPart objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?BodyPart
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
