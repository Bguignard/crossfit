<?php

namespace App\Entity\Workout;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\ConvertibleToDTOInterface;
use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use App\Repository\Workout\WorkoutOriginRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WorkoutOriginRepository::class)]
#[ApiResource]
class WorkoutOriginName implements ConvertibleToDTOInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private string $name;

    public function __construct(
        WorkoutOriginNameEnum $name,
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

    public function getNameAsEnum(): WorkoutOriginNameEnum
    {
        return WorkoutOriginNameEnum::from($this->name);
    }
}
