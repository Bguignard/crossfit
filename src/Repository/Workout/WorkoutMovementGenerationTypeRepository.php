<?php

namespace App\Repository\Workout;

use App\Entity\Workout\WorkoutMovementGenerationType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkoutMovementGenerationType>
 *
 * @method WorkoutMovementGenerationType|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkoutMovementGenerationType|null findOneBy(array $criteria, array $orderBy = null)
 * @method WorkoutMovementGenerationType[]    findAll()
 * @method WorkoutMovementGenerationType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkoutMovementGenerationTypeRepository extends ServiceEntityRepository implements WorkoutMovementGenerationTypeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutMovementGenerationType::class);
    }

    public function persist(WorkoutMovementGenerationType $workoutMovementGenerationType): WorkoutMovementGenerationType
    {
        $this->getEntityManager()->persist($workoutMovementGenerationType);
        $this->getEntityManager()->flush();

        return $workoutMovementGenerationType;
    }
}
