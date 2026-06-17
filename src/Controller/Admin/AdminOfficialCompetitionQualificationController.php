<?php

namespace App\Controller\Admin;

use App\Entity\Competition\Competition;
use App\Entity\Competition\CompetitionDivision;
use App\Entity\Competition\CompetitionOfficialQualification;
use App\Entity\Security\User;
use App\Services\Competition\CompetitionOfficialQualificationSuggester;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminOfficialCompetitionQualificationController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/api/admin/official-qualifications', name: 'api_admin_official_qualifications_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $statuses = $this->statusValues($request->query->all('status'));
        $limit = $this->limit($request->query->get('limit'));

        /** @var list<CompetitionOfficialQualification> $qualifications */
        $qualifications = $this->entityManager->getRepository(CompetitionOfficialQualification::class)
            ->createQueryBuilder('qualification')
            ->innerJoin('qualification.competition', 'competition')
            ->addSelect('competition')
            ->andWhere('qualification.status IN (:statuses)')
            ->setParameter('statuses', $statuses)
            ->orderBy('qualification.updatedAt', 'DESC')
            ->addOrderBy('competition.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->json([
            'qualifications' => array_map(fn (CompetitionOfficialQualification $qualification): array => $this->qualificationPayload($qualification, true), $qualifications),
        ]);
    }

    #[Route('/api/admin/official-qualifications/competitions/{id}', name: 'api_admin_official_qualifications_competition', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $competition = $this->competition($id);
        if (!$competition instanceof Competition) {
            return $this->json(['error' => 'Competition not found.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->payload($competition));
    }

    #[Route('/api/admin/official-qualifications/competitions/{id}', name: 'api_admin_set_official_qualification', methods: ['POST'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $competition = $this->competition($id);
        if (!$competition instanceof Competition) {
            return $this->json(['error' => 'Competition not found.'], Response::HTTP_NOT_FOUND);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(['error' => 'JSON body must be an object.'], Response::HTTP_BAD_REQUEST);
        }

        $action = $this->stringValue($payload['action'] ?? null);
        if (!in_array($action, ['confirm', 'dismiss', 'suggest'], true)) {
            return $this->json(['error' => 'action must be confirm, dismiss or suggest.'], Response::HTTP_BAD_REQUEST);
        }

        $circuit = $this->stringValue($payload['circuit'] ?? null) ?? CompetitionOfficialQualificationSuggester::CIRCUIT_CROSSFIT_GAMES;
        $stage = $this->stringValue($payload['stage'] ?? null) ?? CompetitionOfficialQualificationSuggester::STAGE_SEMIFINALS;
        $divisionPattern = $this->stringValue($payload['divisionPattern'] ?? null);
        if ($divisionPattern === null) {
            return $this->json(['error' => 'divisionPattern is required.'], Response::HTTP_BAD_REQUEST);
        }

        $qualification = $this->findQualification($competition, $circuit, $stage, $divisionPattern);
        if (!$qualification instanceof CompetitionOfficialQualification) {
            $qualification = new CompetitionOfficialQualification($competition, $circuit, $stage, $divisionPattern);
            $this->entityManager->persist($qualification);
        }

        $qualification
            ->setSeason($this->season($payload['season'] ?? null, $competition))
            ->setNotes($this->stringValue($payload['notes'] ?? null));

        match ($action) {
            'confirm' => $qualification->confirm($this->adminUser()),
            'dismiss' => $qualification->dismiss(),
            'suggest' => $qualification->suggest(CompetitionOfficialQualification::SOURCE_ADMIN),
        };

        $this->entityManager->flush();

        return $this->json($this->payload($competition));
    }

    private function competition(string $id): ?Competition
    {
        /** @var Competition|null $competition */
        $competition = $this->entityManager->getRepository(Competition::class)->find($id);

        return $competition;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Competition $competition): array
    {
        return [
            'competition' => $this->competitionPayload($competition),
            'divisionOptions' => $this->divisionOptions($competition),
            'qualifications' => $this->qualifications($competition),
        ];
    }

    /**
     * @return list<array{name: string, pattern: string, externalId: string|null}>
     */
    private function divisionOptions(Competition $competition): array
    {
        /** @var list<CompetitionDivision> $divisions */
        $divisions = $this->entityManager->getRepository(CompetitionDivision::class)
            ->createQueryBuilder('division')
            ->andWhere('division.competition = :competition')
            ->setParameter('competition', $competition)
            ->orderBy('division.name', 'ASC')
            ->getQuery()
            ->getResult();

        $options = [];
        foreach ($divisions as $division) {
            $options[$division->getName()] = [
                'name' => $division->getName(),
                'pattern' => $division->getName(),
                'externalId' => $division->getExternalId(),
            ];
        }

        return array_values($options);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function qualifications(Competition $competition): array
    {
        /** @var list<CompetitionOfficialQualification> $qualifications */
        $qualifications = $this->entityManager->getRepository(CompetitionOfficialQualification::class)
            ->createQueryBuilder('qualification')
            ->andWhere('qualification.competition = :competition')
            ->setParameter('competition', $competition)
            ->orderBy('qualification.season', 'DESC')
            ->addOrderBy('qualification.status', 'ASC')
            ->addOrderBy('qualification.divisionPattern', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(fn (CompetitionOfficialQualification $qualification): array => $this->qualificationPayload($qualification), $qualifications);
    }

    /**
     * @return array<string, mixed>
     */
    private function competitionPayload(Competition $competition): array
    {
        return [
            'id' => (string) $competition->getId(),
            'name' => $competition->getName(),
            'season' => $competition->getSeason(),
            'sourceName' => $competition->getSourceName(),
            'externalId' => $competition->getExternalId(),
            'logoUrl' => $competition->getLogoUrl(),
            'startsAt' => $competition->getStartsAt()?->format(\DateTimeInterface::ATOM),
            'endsAt' => $competition->getEndsAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function qualificationPayload(CompetitionOfficialQualification $qualification, bool $includeCompetition = false): array
    {
        $payload = [
            'id' => (string) $qualification->getId(),
            'circuit' => $qualification->getCircuit(),
            'stage' => $qualification->getStage(),
            'divisionPattern' => $qualification->getDivisionPattern(),
            'season' => $qualification->getSeason(),
            'status' => $qualification->getStatus(),
            'source' => $qualification->getSource(),
            'notes' => $qualification->getNotes(),
            'label' => $this->label($qualification),
            'confirmedAt' => $qualification->getConfirmedAt()?->format(\DateTimeInterface::ATOM),
            'dismissedAt' => $qualification->getDismissedAt()?->format(\DateTimeInterface::ATOM),
        ];

        if ($includeCompetition) {
            $payload['competition'] = $this->competitionPayload($qualification->getCompetition());
        }

        return $payload;
    }

    private function findQualification(
        Competition $competition,
        string $circuit,
        string $stage,
        string $divisionPattern,
    ): ?CompetitionOfficialQualification {
        /** @var CompetitionOfficialQualification|null $qualification */
        $qualification = $this->entityManager->getRepository(CompetitionOfficialQualification::class)->findOneBy([
            'competition' => $competition,
            'circuit' => $circuit,
            'stage' => $stage,
            'divisionPattern' => $divisionPattern,
        ]);

        return $qualification;
    }

    private function label(CompetitionOfficialQualification $qualification): string
    {
        $parts = [
            $qualification->getCircuit() === CompetitionOfficialQualificationSuggester::CIRCUIT_CROSSFIT_GAMES
                ? 'CrossFit Games'
                : $this->humanize($qualification->getCircuit()),
            $qualification->getStage() === CompetitionOfficialQualificationSuggester::STAGE_SEMIFINALS
                ? 'Semifinal'
                : $this->humanize($qualification->getStage()),
            $qualification->getSeason(),
        ];

        return trim(implode(' ', array_filter(array_map(
            static fn (mixed $part): ?string => $part === null || $part === '' ? null : (string) $part,
            $parts,
        ))));
    }

    private function humanize(string $value): string
    {
        return mb_convert_case(str_replace('_', ' ', $value), MB_CASE_TITLE, 'UTF-8');
    }

    private function stringValue(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function season(mixed $value, Competition $competition): ?int
    {
        $season = $this->stringValue($value);
        if ($season === null) {
            return $competition->getSeason();
        }

        return (int) $season;
    }

    /**
     * @param list<mixed> $values
     *
     * @return non-empty-list<string>
     */
    private function statusValues(array $values): array
    {
        $allowedStatuses = [
            CompetitionOfficialQualification::STATUS_CONFIRMED,
            CompetitionOfficialQualification::STATUS_SUGGESTED,
            CompetitionOfficialQualification::STATUS_DISMISSED,
        ];
        $statuses = [];

        foreach ($values as $value) {
            foreach (explode(',', (string) $value) as $status) {
                $status = trim($status);
                if (in_array($status, $allowedStatuses, true)) {
                    $statuses[$status] = $status;
                }
            }
        }

        return array_values($statuses) ?: [
            CompetitionOfficialQualification::STATUS_CONFIRMED,
            CompetitionOfficialQualification::STATUS_SUGGESTED,
        ];
    }

    private function limit(mixed $value): int
    {
        $limit = (int) $this->stringValue($value);

        return min(100, max(1, $limit ?: 50));
    }

    private function adminUser(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }
}
