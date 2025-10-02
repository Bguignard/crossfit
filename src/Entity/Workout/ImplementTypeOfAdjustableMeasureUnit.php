<?php

namespace App\Entity\Workout;

use App\Entity\Workout\Enum\ImplementTypeOfMeasureEnum;
use App\Repository\Workout\ImplementTypeOfMeasureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ImplementTypeOfMeasureRepository::class)]
class ImplementTypeOfAdjustableMeasureUnit
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'string', nullable: false, enumType: ImplementTypeOfMeasureEnum::class)]
    private ImplementTypeOfMeasureEnum $implementTypeOfMeasureEnum;

    #[ORM\ManyToMany(targetEntity: MeasureUnit::class, inversedBy: 'implementTypeOfAdjustableMeasureUnit')]
    private Collection $measureUnits;

    public function __construct(
        ImplementTypeOfMeasureEnum $implementTypeOfMeasureEnum,
    ) {
        $this->measureUnits = new ArrayCollection();
        $this->implementTypeOfMeasureEnum = $implementTypeOfMeasureEnum;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getImplementTypeOfMeasureEnum(): ImplementTypeOfMeasureEnum
    {
        return $this->implementTypeOfMeasureEnum;
    }

    public function addMeasureUnit(MeasureUnit $measureUnit): static
    {
        if (!$this->measureUnits->contains($measureUnit)) {
            $this->measureUnits->add($measureUnit);
        }

        return $this;
    }

    public function setMeasureUnits(array $measureUnits): static
    {
        foreach ($measureUnits as $measureUnit) {
            $this->addMeasureUnit($measureUnit);
        }

        return $this;
    }

    public function getMeasureUnits(): Collection
    {
        return $this->measureUnits;
    }
}
