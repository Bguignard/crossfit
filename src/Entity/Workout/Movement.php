<?php

namespace App\Entity\Workout;

use App\Repository\Workout\MovementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MovementRepository::class)]
class Movement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: false)]
    private string $name;

    #[ORM\ManyToMany(targetEntity: BodyPart::class, inversedBy: 'movements')]
    private Collection $bodyParts;

    #[ORM\Column(nullable: false)]
    private int $difficulty;

    #[ORM\Column(type: 'string', enumType: MovementType::class, nullable: false)]
    private MovementType $movementType;

    public function __construct(
        string $name,
        int $difficulty,
        MovementType $movementType
    ) {
        $this->bodyParts = new ArrayCollection();
        $this->name = $name;
        $this->difficulty = $difficulty;
        $this->movementType = $movementType;
    }

    public function getId(): ?int
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

    public function getMovementType(): MovementType
    {
        return $this->movementType;
    }

    public function setBodyParts(Collection $bodyParts): Movement
    {
        $this->bodyParts = $bodyParts;

        return $this;
    }
}
