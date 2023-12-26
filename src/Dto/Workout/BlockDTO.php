<?php

namespace App\Dto\Workout;

use App\Entity\Workout\Block;
use Symfony\Component\Uid\Uuid;

final readonly class BlockDTO
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

    public static function createFromEntity(Block $block): self
    {
        $movementClusters = [];
        foreach ($block->getMovementClusters() as $movementCluster) {
            $movementClusters[] = MovementClusterDTO::createFromEntity($movementCluster);
        }

        return new self(
            $block->getId(),
            $block->getRounds(),
            $block->getOrderInWorkout(),
            $movementClusters,
            $block->getRestTime(),
        );
    }
}
