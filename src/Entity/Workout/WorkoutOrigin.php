<?php

namespace App\Entity\Workout;

use App\Enum\WorkoutOriginNameEnum;
use App\Repository\Workout\WorkoutOriginRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WorkoutOriginRepository::class)]
class WorkoutOrigin
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'string', nullable: true, enumType: WorkoutOriginNameEnum::class)]
    private ?WorkoutOriginNameEnum $name;

    #[ORM\Column(nullable: true)]
    private ?int $year;

    public function __construct(
        ?WorkoutOriginNameEnum $name,
        ?int $year
    ) {
        $this->name = $name;
        $this->year = $year;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): ?WorkoutOriginNameEnum
    {
        return $this->name;
    }

    public function setName(?WorkoutOriginNameEnum $name): WorkoutOrigin
    {
        $this->name = $name;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): WorkoutOrigin
    {
        $this->year = $year;

        return $this;
    }
}
