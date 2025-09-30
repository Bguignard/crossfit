<?php

namespace App\Repository\Workout;

use App\Entity\Workout\WorkoutType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkoutType>
 *
 * @method WorkoutType|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkoutType|null findOneBy(array $criteria, array $orderBy = null)
 * @method WorkoutType[]    findAll()
 * @method WorkoutType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkoutTypeRepository extends ServiceEntityRepository implements WorkoutTypeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutType::class);
    }

    public function persist(WorkoutType $workoutType): WorkoutType
    {
        $this->getEntityManager()->persist($workoutType);
        $this->getEntityManager()->flush();

        return $workoutType;
    }
}
