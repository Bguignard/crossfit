<?php

namespace App\Repository\Workout;

use App\Entity\Workout\SimpleWorkout;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SimpleWorkout>
 *
 * @method SimpleWorkout|null find($id, $lockMode = null, $lockVersion = null)
 * @method SimpleWorkout|null findOneBy(array $criteria, array $orderBy = null)
 * @method SimpleWorkout[]    findAll()
 * @method SimpleWorkout[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SimpleWorkoutRepository extends ServiceEntityRepository implements SimpleWorkoutRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SimpleWorkout::class);
    }

    public function persist(SimpleWorkout $workoutOriginName): SimpleWorkout
    {
        $this->getEntityManager()->persist($workoutOriginName);
        $this->getEntityManager()->flush();

        return $workoutOriginName;
    }
}
