<?php

namespace App\Entity\Workout;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\ConvertibleToDTOInterface;
use App\Entity\Workout\Enum\MuscleEnum;
use App\Repository\Workout\MuscleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MuscleRepository::class)]
#[ApiResource]
class Muscle implements ConvertibleToDTOInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: 'string', nullable: false)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: BodyPart::class)]
    #[ORM\JoinColumn(nullable: false)]
    private BodyPart $bodyPart;

    #[ORM\ManyToMany(targetEntity: Movement::class, inversedBy: 'muscles')]
    private Collection $movements;

    public function __construct(MuscleEnum $muscleEnum, BodyPart $bodyPart)
    {
        $this->bodyPart = $bodyPart;
        $this->name = $muscleEnum->value;
        $this->movements = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNameAsEnum(): MuscleEnum
    {
        return MuscleEnum::from($this->name);
    }

    public function getBodyPart(): BodyPart
    {
        return $this->bodyPart;
    }

    public function getMovements(): Collection
    {
        return $this->movements;
    }

    public function setMovements(Collection $movements): Muscle
    {
        $this->movements = $movements;

        return $this;
    }
}
