<?php

namespace App\Entity\Workout;

use App\Entity\Workout\Enum\MovementTypeEnum;
use App\Repository\Workout\MovementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MovementRepository::class)]
class Movement
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255, nullable: false)]
    private string $name;

    #[ORM\ManyToMany(targetEntity: BodyPart::class, inversedBy: 'movements')]
    private Collection $bodyParts;

    #[ORM\Column(nullable: false)]
    private int $difficulty;

    #[ORM\Column(type: 'string', enumType: MovementTypeEnum::class)]
    private MovementTypeEnum $movementType;

    #[ORM\ManyToMany(targetEntity: Implement::class, inversedBy: 'movements')]
    private Collection $possibleImplements;

    public function __construct(
        string $name,
        int $difficulty,
        MovementTypeEnum $movementType,
    ) {
        $this->possibleImplements = new ArrayCollection();
        $this->bodyParts = new ArrayCollection();
        $this->name = $name;
        $this->difficulty = $difficulty;
        $this->movementType = $movementType;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, BodyPart>
     */
    public function getBodyParts(): Collection
    {
        return $this->bodyParts;
    }

    public function addBodyPart(BodyPart $bodyPart): static
    {
        if (!$this->bodyParts->contains($bodyPart)) {
            $this->bodyParts->add($bodyPart);
        }

        return $this;
    }

    public function removeBodyPart(BodyPart $bodyPart): static
    {
        $this->bodyParts->removeElement($bodyPart);

        return $this;
    }

    public function getDifficulty(): ?int
    {
        return $this->difficulty;
    }

    public function setDifficulty(int $difficulty): static
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    public function getMovementType(): MovementTypeEnum
    {
        return $this->movementType;
    }

    public function setBodyParts(array $bodyParts): Movement
    {
        foreach ($bodyParts as $bodyPart) {
            $this->addBodyPart($bodyPart);
        }

        return $this;
    }

    public function addPossibleImplement(Implement $implement): Movement
    {
        if (!$this->possibleImplements->contains($implement)) {
            $this->possibleImplements->add($implement);
        }

        return $this;
    }

    public function setPossibleImplements(array $possibleImplements): Movement
    {
        foreach ($possibleImplements as $possibleImplement) {
            $this->addPossibleImplement($possibleImplement);
        }

        return $this;
    }

    public function getPossibleImplements(): Collection
    {
        return $this->possibleImplements;
    }
}
