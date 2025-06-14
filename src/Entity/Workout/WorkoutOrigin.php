<?php

namespace App\Entity\Workout;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\Workout\WorkoutOriginRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WorkoutOriginRepository::class)]
#[ApiResource]
class WorkoutOrigin
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: WorkoutOriginName::class, cascade: ['persist'])]
    private WorkoutOriginName $name;

    #[ORM\Column(nullable: true)]
    private ?int $year;

    public function __construct(
        WorkoutOriginName $name,
        ?int $year,
    ) {
        $this->name = $name;
        $this->year = $year;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): WorkoutOriginName
    {
        return $this->name;
    }

    public function setName(WorkoutOriginName $name): WorkoutOrigin
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
