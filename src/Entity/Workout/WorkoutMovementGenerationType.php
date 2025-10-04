<?php

namespace App\Entity\Workout;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Workout\Enum\WorkoutMovementGenerationTypeEnum;
use App\Repository\Workout\WorkoutMovementGenerationTypeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WorkoutMovementGenerationTypeRepository::class)]
#[ApiResource]
class WorkoutMovementGenerationType
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private string $name;

    public function __construct(
        WorkoutMovementGenerationTypeEnum $name,
    ) {
        $this->name = $name->value;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNameAsEnum(): WorkoutMovementGenerationTypeEnum
    {
        return WorkoutMovementGenerationTypeEnum::from($this->name);
    }
}
