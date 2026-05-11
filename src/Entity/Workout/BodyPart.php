<?php

namespace App\Entity\Workout;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Entity\Workout\Enum\BodyPartEnum;
use App\Repository\Workout\BodyPartRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: BodyPartRepository::class)]
#[ApiResource(operations: [new Get(), new GetCollection()])]
class BodyPart
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\OneToMany(mappedBy: 'bodyPart', targetEntity: Muscle::class)]
    private Collection $muscles;

    public function __construct(BodyPartEnum $bodyPart)
    {
        $this->muscles = new ArrayCollection();
        $this->name = $bodyPart->value;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNameAsEnum(): BodyPartEnum
    {
        return BodyPartEnum::from($this->name);
    }

    public function getMuscles(): array
    {
        return $this->muscles->toArray();
    }

    public function setMuscles(array $muscles): BodyPart
    {
        $this->muscles = new ArrayCollection($muscles);

        return $this;
    }

    public function addMuscle(Muscle $muscle): BodyPart
    {
        if (!$this->muscles->contains($muscle)) {
            $this->muscles->add($muscle);
        }

        return $this;
    }
}
