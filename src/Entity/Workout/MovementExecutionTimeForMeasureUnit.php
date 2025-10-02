<?php

namespace App\Entity\Workout;

use App\Entity\Workout\Enum\MeasureUnitEnum;
use App\Repository\Workout\MovementExecutionTimeForMeasureUnitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MovementExecutionTimeForMeasureUnitRepository::class)]
class MovementExecutionTimeForMeasureUnit
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;
    #[ORM\Column(type: 'string', enumType: MeasureUnitEnum::class)]
    private MeasureUnitEnum $measureUnit;

    // The time to execute one rep of the movement in millisecond at 50% of the max intensity
    #[ORM\Column(nullable: false)]
    private int $executionTimeInMilliseconds;

    public function __construct(MeasureUnitEnum $measureUnit, int $executionTimeInMilliseconds)
    {
        $this->measureUnit = $measureUnit;
        $this->executionTimeInMilliseconds = $executionTimeInMilliseconds;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getMeasureUnit(): MeasureUnitEnum
    {
        return $this->measureUnit;
    }

    public function getExecutionTimeInMilliseconds(): int
    {
        return $this->executionTimeInMilliseconds;
    }
}
