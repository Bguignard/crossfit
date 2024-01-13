<?php

namespace App\Entity\Workout;

use App\Entity\Workout\Enum\ImplementEnum;
use App\Entity\Workout\Enum\ImplementTypeOfMeasureEnum;
use App\Repository\Workout\ImplementRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ImplementRepository::class)]
class Implement
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255, nullable: false)]
    private string $name;

    #[ORM\Column(type: 'string', nullable: true, enumType: ImplementTypeOfMeasureEnum::class)]
    private ImplementTypeOfMeasureEnum $implementTypeOfAdjustableMeasure;

    public function __construct(ImplementEnum $name, ImplementTypeOfMeasureEnum $implementTypeOfAdjustableMeasure)
    {
        $this->name = $name->value;
        $this->implementTypeOfAdjustableMeasure = $implementTypeOfAdjustableMeasure;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNameAsEnum(): ImplementEnum
    {
        return ImplementEnum::from($this->name);
    }

    public function getImplementTypeOfAdjustableMeasure(): ImplementTypeOfMeasureEnum
    {
        return $this->implementTypeOfAdjustableMeasure;
    }

    public function setImplementTypeOfAdjustableMeasure(ImplementTypeOfMeasureEnum $implementTypeOfAdjustableMeasure): Implement
    {
        $this->implementTypeOfAdjustableMeasure = $implementTypeOfAdjustableMeasure;

        return $this;
    }
}
