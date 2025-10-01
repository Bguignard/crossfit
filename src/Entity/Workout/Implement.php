<?php

namespace App\Entity\Workout;

use ApiPlatform\Metadata\ApiResource;

use App\Entity\Workout\Enum\ImplementEnum;
use App\Repository\Workout\ImplementRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ImplementRepository::class)]
#[ApiResource]
class Implement
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255, nullable: false)]
    private string $name;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?ImplementTypeOfAdjustableMeasureUnit $implementTypeOfAdjustableMeasure;

    public function __construct(ImplementEnum $name, ?ImplementTypeOfAdjustableMeasureUnit $implementTypeOfAdjustableMeasure)
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

    public function getImplementTypeOfAdjustableMeasure(): ?ImplementTypeOfAdjustableMeasureUnit
    {
        return $this->implementTypeOfAdjustableMeasure;
    }

    public function setImplementTypeOfAdjustableMeasure(?ImplementTypeOfAdjustableMeasureUnit $implementTypeOfAdjustableMeasure): Implement
    {
        $this->implementTypeOfAdjustableMeasure = $implementTypeOfAdjustableMeasure;

        return $this;
    }
}
