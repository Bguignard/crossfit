<?php

namespace App\Repository\Workout;

use App\Entity\Workout\MovementCluster;

interface MovementClusterRepositoryInterface
{
    public function persist(MovementCluster $movementCluster): void;
}
