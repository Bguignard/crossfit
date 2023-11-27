<?php

namespace App\Entity\Workout;

use App\Enum\RepUnit;
use App\Repository\Workout\MovementDetailRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MovementDetailRepository::class)]
class MovementDetail
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(nullable: true)]
    private ?float $movementIntensity; // For example the weight, the distance, the height, etc.

    #[ORM\Column(type: 'string', nullable: true, enumType: RepUnit::class)]
    private ?RepUnit $movementIntensityUnit; // For example kg, lbs, meters, feet, etc. of MOVEMENT INTENSITY

    public function __construct(
        ?float $movementIntensity,
        ?RepUnit $movementIntensityUnit
    ) {
        $this->movementIntensity = $movementIntensity;
        $this->movementIntensityUnit = $movementIntensityUnit;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getMovementIntensity(): ?float
    {
        return $this->movementIntensity;
    }

    public function setMovementIntensity(float $movementIntensity): static
    {
        $this->movementIntensity = $movementIntensity;

        return $this;
    }

    public function getMovementIntensityUnit(): ?RepUnit
    {
        return $this->movementIntensityUnit;
    }

    public function setMovementIntensityUnit(?RepUnit $movementIntensityUnit): MovementDetail
    {
        $this->movementIntensityUnit = $movementIntensityUnit;

        return $this;
    }
}
