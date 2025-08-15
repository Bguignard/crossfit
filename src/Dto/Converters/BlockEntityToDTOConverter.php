<?php

namespace App\Dto\Converters;

use App\Dto\Workout\BlockDTO;
use App\Entity\ConvertibleToDTOInterface;
use App\Entity\Workout\Block;

class BlockEntityToDTOConverter implements EntityToDTOConverterInterface
{
    public static function createFromEntity(ConvertibleToDTOInterface $block): BlockDTO
    {
        if (!($block instanceof Block)) {
            throw new \InvalidArgumentException(sprintf('Entity must be of type % ', Block::class));
        }
        $movementClusters = [];
        foreach ($block->getMovementClusters() as $movementCluster) {
            $movementClusters[] = MovementClusterEntityToDTOConverter::createFromEntity($movementCluster);
        }

        return new BlockDTO(
            $block->getId(),
            $block->getRounds(),
            $block->getOrderInWorkout(),
            $movementClusters,
            $block->getRestTime(),
        );
    }
}
