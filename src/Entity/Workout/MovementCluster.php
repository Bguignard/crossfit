<?php

namespace App\Entity\Workout;

use App\Entity\Workout\Enum\MeasureUnitEnum;
use App\Repository\Workout\MovementClusterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MovementClusterRepository::class)]
class MovementCluster
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column]
    private int $repetitions; // For example 5 reps, 10 reps, etc. if its 500m run its 500 rep

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Movement $movement;

    #[ORM\Column(nullable: true)]
    private ?float $implementIntensityAdjustmentValue; // For example the weight, the distance, the height, etc.

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?ImplementTypeOfAdjustableMeasureUnit $movementDetailIntensityUnit; // For example kg, lbs, meters, feet, etc. of MOVEMENT INTENSITY

    #[ORM\Column(type: 'string', nullable: true, enumType: MeasureUnitEnum::class)]
    private MeasureUnitEnum $repUnit; // For example kg, lbs, meters, feet, etc. of REPETITIONS

    #[ORM\ManyToMany(targetEntity: Implement::class)]
    private Collection $implements;

    public function __construct(
        int $repetitions,
        MeasureUnitEnum $repUnit,
        array $implements,
        Movement $movement,
        float $implementIntensityAdjustmentValue = null,
        ImplementTypeOfAdjustableMeasureUnit $implementIntensityUnit = null,
    ) {
        $this->implements = new ArrayCollection();
        $this->repetitions = $repetitions;
        foreach ($implements as $implement) {
            $this->addImplement($implement);
        }
        $this->movement = $movement;
        $this->repUnit = $repUnit;
        $this->implementIntensityAdjustmentValue = $implementIntensityAdjustmentValue;
        $this->movementDetailIntensityUnit = $implementIntensityUnit;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getMovement(): ?Movement
    {
        return $this->movement;
    }

    public function getRepUnit(): MeasureUnitEnum
    {
        return $this->repUnit;
    }

    public function addImplement(Implement $implement): MovementCluster
    {
        if (!$this->implements->contains($implement)) {
            $this->implements->add($implement);
        }

        return $this;
    }

    public function getImplements(): Collection
    {
        return $this->implements;
    }

    public function getRepetitions(): int
    {
        return $this->repetitions;
    }

    public function getImplementIntensityAdjustmentValue(): ?float
    {
        return $this->implementIntensityAdjustmentValue;
    }

    public function getMovementDetailIntensityUnit(): ?ImplementTypeOfAdjustableMeasureUnit
    {
        return $this->movementDetailIntensityUnit;
    }
}
