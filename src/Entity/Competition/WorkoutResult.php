<?php

namespace App\Entity\Competition;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'workout_result')]
#[ORM\UniqueConstraint(name: 'UNIQ_WORKOUT_RESULT_SOURCE_EXTERNAL', columns: ['source_name', 'external_id'])]
#[ApiResource(operations: [new Get(), new GetCollection()])]
#[ApiFilter(SearchFilter::class, properties: [
    'athlete' => 'exact',
    'event' => 'exact',
    'event.competition' => 'exact',
    'competitionDivision' => 'exact',
    'division' => 'ipartial',
    'sourceName' => 'exact',
    'externalId' => 'exact',
    'rank' => 'exact',
])]
#[ApiFilter(OrderFilter::class, properties: ['rank', 'points', 'createdAt', 'updatedAt'])]
class WorkoutResult
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: Athlete::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Athlete $athlete;

    #[ORM\ManyToOne(targetEntity: CompetitionEvent::class, inversedBy: 'results')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private CompetitionEvent $event;

    #[ORM\ManyToOne(targetEntity: CompetitionDivision::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?CompetitionDivision $competitionDivision = null;

    #[ORM\OneToOne(targetEntity: Score::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private Score $score;

    #[ORM\Column(nullable: true)]
    private ?int $rank = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $division = null;

    #[ORM\Column(nullable: true)]
    private ?int $points = null;

    #[ORM\Column(length: 64)]
    private string $sourceName;

    #[ORM\Column(length: 255)]
    private string $externalId;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $sourceUrl = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        Athlete $athlete,
        CompetitionEvent $event,
        Score $score,
        string $sourceName,
        string $externalId,
    ) {
        $this->athlete = $athlete;
        $this->event = $event;
        $this->score = $score;
        $this->sourceName = $sourceName;
        $this->externalId = $externalId;
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

    public function getEvent(): CompetitionEvent
    {
        return $this->event;
    }

    public function getCompetitionDivision(): ?CompetitionDivision
    {
        return $this->competitionDivision;
    }

    public function setCompetitionDivision(?CompetitionDivision $competitionDivision): self
    {
        $this->competitionDivision = $competitionDivision;
        $this->touch();

        return $this;
    }

    public function getScore(): Score
    {
        return $this->score;
    }

    public function setScore(Score $score): self
    {
        $this->score = $score;
        $this->touch();

        return $this;
    }

    public function getRank(): ?int
    {
        return $this->rank;
    }

    public function setRank(?int $rank): self
    {
        $this->rank = $rank;
        $this->touch();

        return $this;
    }

    public function getDivision(): ?string
    {
        return $this->division;
    }

    public function setDivision(?string $division): self
    {
        $this->division = $division;
        $this->touch();

        return $this;
    }

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(?int $points): self
    {
        $this->points = $points;
        $this->touch();

        return $this;
    }

    public function getSourceName(): string
    {
        return $this->sourceName;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function getSourceUrl(): ?string
    {
        return $this->sourceUrl;
    }

    public function setSourceUrl(?string $sourceUrl): self
    {
        $this->sourceUrl = $sourceUrl;
        $this->touch();

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
