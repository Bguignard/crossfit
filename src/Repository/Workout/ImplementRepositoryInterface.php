<?php

namespace App\Repository\Workout;

use App\Entity\Workout\Implement;

interface ImplementRepositoryInterface
{
    /**
     * @return list<Implement>
     */
    public function findAll(): array;
}
