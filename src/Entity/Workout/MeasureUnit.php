<?php

namespace App\Entity\Workout;

use App\Entity\Workout\Enum\MeasureUnitEnum;
use App\Repository\Workout\MeasureUnitRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MeasureUnitRepository::class)]
class MeasureUnit
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    public function __construct(MeasureUnitEnum $measureUnit)
    {
        $this->name = $measureUnit->value;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
