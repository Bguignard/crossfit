<?php

namespace App\Repository\Workout;

use App\Entity\Workout\BodyPart;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BodyPart>
 *
 * @method BodyPart|null find($id, $lockMode = null, $lockVersion = null)
 * @method BodyPart|null findOneBy(array $criteria, array $orderBy = null)
 * @method BodyPart[]    findAll()
 * @method BodyPart[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BodyPartRepository extends ServiceEntityRepository implements BodyPartRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BodyPart::class);
    }
}
