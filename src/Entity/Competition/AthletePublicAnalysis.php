<?php

namespace App\Entity\Competition;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'athlete_public_analysis')]
#[ORM\UniqueConstraint(name: 'UNIQ_ATHLETE_PUBLIC_ANALYSIS_KIND', columns: ['athlete_id', 'kind'])]
class AthletePublicAnalysis
{
    public const KIND_GAMES_PUBLIC = 'games_public_v1';

    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Athlete::class, inversedBy: 'publicAnalyses')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Athlete $athlete;

    #[ORM\Column(length: 64)]
    private string $kind;

    #[ORM\Column(length: 64)]
    private string $promptHash;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: 'json')]
    private array $analysis = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $generatedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    /**
     * @param array<string, mixed> $analysis
     */
    public function __construct(Athlete $athlete, string $kind, string $promptHash, array $analysis)
    {
        $this->athlete = $athlete;
        $this->kind = $kind;
        $this->promptHash = $promptHash;
        $this->analysis = $analysis;
        $this->generatedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getAthlete(): Athlete
    {
        return $this->athlete;
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function getPromptHash(): string
    {
        return $this->promptHash;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAnalysis(): array
    {
        return $this->analysis;
    }

    /**
     * @param array<string, mixed> $analysis
     */
    public function replace(string $promptHash, array $analysis): self
    {
        $this->promptHash = $promptHash;
        $this->analysis = $analysis;
        $this->generatedAt = new \DateTimeImmutable();
        $this->touch();

        return $this;
    }

    public function getGeneratedAt(): \DateTimeImmutable
    {
        return $this->generatedAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isOlderThan(\DateInterval $maxAge): bool
    {
        return $this->generatedAt <= (new \DateTimeImmutable())->sub($maxAge);
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicPayload(): array
    {
        return [
            ...$this->analysis,
            'generatedAt' => $this->generatedAt->format(\DateTimeInterface::ATOM),
        ];
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
