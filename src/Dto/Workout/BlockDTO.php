<?php

namespace App\Dto\Workout;

use App\Dto\DTOFromEntityInterface;
use Symfony\Component\Uid\Uuid;

final readonly class BlockDTO implements DTOFromEntityInterface
{
    public function __construct(
        public ?Uuid $id,
        public ?int $rounds,
        public ?int $orderInWorkout,
        /**
         * @var MovementClusterDTO[]
         */
        public array $movementClusters,
        public ?int $restTime,
    ) {
    }
}
