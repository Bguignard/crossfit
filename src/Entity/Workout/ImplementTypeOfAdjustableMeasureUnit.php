<?php

namespace App\Entity\Workout;

use App\Entity\Workout\Enum\ImplementTypeOfMeasureEnum;
use App\Entity\Workout\Enum\MeasureUnitEnum;
use App\Repository\Workout\ImplementTypeOfMeasureRepository;
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

    #[ORM\Column(type: 'string', nullable: false, enumType: MeasureUnitEnum::class)]
    private MeasureUnitEnum $measureUnitEnum;

    public function __construct(
        ImplementTypeOfMeasureEnum $implementTypeOfMeasureEnum,
        MeasureUnitEnum $measureUnitEnum,
    ) {
        $this->implementTypeOfMeasureEnum = $implementTypeOfMeasureEnum;
        $this->measureUnitEnum = $measureUnitEnum;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getImplementTypeOfMeasureEnum(): ImplementTypeOfMeasureEnum
    {
        return $this->implementTypeOfMeasureEnum;
    }

    public function getMeasureUnitEnum(): MeasureUnitEnum
    {
        return $this->measureUnitEnum;
    }
}
