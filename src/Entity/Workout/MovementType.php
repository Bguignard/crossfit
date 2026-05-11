<?php

namespace App\Entity\Workout;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Workout\Enum\MovementTypeEnum;
use App\Repository\Workout\MovementTypeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MovementTypeRepository::class)]
#[ApiResource(operations: [new Get(), new GetCollection()])]
class MovementType
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private string $name;

    public function __construct(
        MovementTypeEnum $name,
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

    public function getNameAsEnum(): MovementTypeEnum
    {
        return MovementTypeEnum::from($this->name);
    }
}
