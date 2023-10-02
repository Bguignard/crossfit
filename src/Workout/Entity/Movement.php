<?php

namespace App\Workout\Entity;

use App\Workout\Repository\MovementRepository;
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

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToMany(targetEntity: BodyPart::class, inversedBy: 'movements')]
    private Collection $bodyParts;

    #[ORM\Column]
    private ?int $difficulty = null;

    public function __construct()
    {
        $this->bodyParts = new ArrayCollection();
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
}
