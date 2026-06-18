<?php

namespace App\Controller\Competition;

use App\Entity\Competition\Athlete;
use App\Entity\Competition\CompetitionParticipation;
use App\Entity\Competition\WorkoutResult;
use App\Entity\Workout\Workout;
use App\Services\Competition\AthleteNameNormalizer;
use App\Services\Competition\CompetitionOfficialQualificationPresenter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class AthleteResultSummaryController extends AbstractController
{
    public function __construct(private readonly CompetitionOfficialQualificationPresenter $qualificationPresenter)
    {
    }

    #[Route('/api/athletes/{id}/result-summary', name: 'api_athlete_result_summary', methods: ['GET'])]
    public function __invoke(
        string $id,
        EntityManagerInterface $entityManager,
        AthleteNameNormalizer $athleteNameNormalizer,
    ): JsonResponse {
        $athlete = $entityManager->getRepository(Athlete::class)->find($id);

        if (!$athlete instanceof Athlete) {
            throw new NotFoundHttpException('Athlete not found.');
        }

        $relatedAthletes = $this->relatedAthletes($athlete, $entityManager, $athleteNameNormalizer);

        $results = $entityManager->createQueryBuilder()
            ->select('result', 'score', 'event', 'competition', 'division', 'workout')
            ->from(WorkoutResult::class, 'result')
            ->join('result.score', 'score')
            ->join('result.event', 'event')
            ->join('event.competition', 'competition')
            ->leftJoin('result.competitionDivision', 'division')
            ->leftJoin('event.workout', 'workout')
            ->andWhere('result.athlete IN (:athletes)')
            ->setParameter('athletes', $relatedAthletes)
            ->orderBy('result.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
        $participations = $entityManager->createQueryBuilder()
            ->select('participation', 'competition')
            ->from(CompetitionParticipation::class, 'participation')
            ->join('participation.competition', 'competition')
            ->andWhere('participation.athlete IN (:athletes)')
            ->andWhere('competition.startsAt > :now OR (competition.startsAt IS NULL AND competition.status = :upcoming)')
            ->andWhere('NOT EXISTS (
                SELECT 1
                FROM '.WorkoutResult::class.' existingResult
                JOIN existingResult.event existingEvent
                WHERE existingResult.athlete = participation.athlete
                AND existingEvent.competition = competition
            )')
            ->setParameter('athletes', $relatedAthletes)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('upcoming', 'upcoming')
            ->orderBy('competition.startsAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->json([
            'totalItems' => count($results),
            'member' => array_map([$this, 'resultPayload'], $results),
            'upcomingParticipations' => array_map([$this, 'participationPayload'], $participations),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resultPayload(WorkoutResult $result): array
    {
        $event = $result->getEvent();
        $competition = $event->getCompetition();
        $division = $result->getCompetitionDivision();
        $divisionName = $result->getDivision() ?? $division?->getName();
        $score = $result->getScore();
        $workout = $event->getWorkout();

        return [
            '@id' => '/api/workout_results/'.$result->getId(),
            'id' => (string) $result->getId(),
            'athlete' => '/api/athletes/'.$result->getAthlete()->getId(),
            'event' => '/api/competition_events/'.$event->getId(),
            'competitionDivision' => $division ? '/api/competition_divisions/'.$division->getId() : null,
            'score' => '/api/scores/'.$score->getId(),
            'rank' => $result->getRank(),
            'fieldSize' => $result->getFieldSize(),
            'rankContext' => $this->rankContext($result),
            'division' => $divisionName,
            'participationDetails' => [
                'rank' => $result->getCompetitionRank(),
                'division' => $divisionName,
                'divisionSourceId' => $result->getDivisionSourceId(),
                'format' => $result->getCompetitionFormat(),
                'formatSlug' => $result->getCompetitionFormatSlug(),
            ],
            'points' => $result->getPoints(),
            'sourceName' => $result->getSourceName(),
            'externalId' => $result->getExternalId(),
            'sourceUrl' => $result->getSourceUrl(),
            'createdAt' => $result->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $result->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            'scoreDetails' => [
                '@id' => '/api/scores/'.$score->getId(),
                'id' => (string) $score->getId(),
                'type' => $score->getType()->value,
                'rawValue' => $score->getRawValue(),
                'displayValue' => $score->getDisplayValue(),
                'numericValue' => $score->getNumericValue(),
                'timeInSeconds' => $score->getTimeInSeconds(),
                'unit' => $score->getUnit(),
            ],
            'eventDetails' => [
                '@id' => '/api/competition_events/'.$event->getId(),
                'id' => (string) $event->getId(),
                'competition' => '/api/competitions/'.$competition->getId(),
                'workout' => $workout instanceof Workout ? '/api/workouts/'.$workout->getId() : null,
                'name' => $event->getName(),
                'eventOrder' => $event->getEventOrder(),
                'sourceName' => $event->getSourceName(),
                'externalId' => $event->getExternalId(),
                'sourceUrl' => $event->getSourceUrl(),
            ],
            'competitionDetails' => [
                '@id' => '/api/competitions/'.$competition->getId(),
                'id' => (string) $competition->getId(),
                'name' => $competition->getName(),
                'season' => $competition->getSeason(),
                'status' => $competition->getStatus(),
                'sourceName' => $competition->getSourceName(),
                'externalId' => $competition->getExternalId(),
                'sourceUrl' => $competition->getSourceUrl(),
                'logoUrl' => $competition->getLogoUrl(),
                'startsAt' => $competition->getStartsAt()?->format(\DateTimeInterface::ATOM),
                'endsAt' => $competition->getEndsAt()?->format(\DateTimeInterface::ATOM),
                'locationLabel' => $competition->getLocationLabel(),
                'competitionType' => $competition->getCompetitionType(),
                'participationType' => $competition->getParticipationType(),
                'officialQualifications' => $this->qualificationPresenter->confirmedPayload($competition),
            ],
            'competitionDivisionDetails' => $division ? [
                '@id' => '/api/competition_divisions/'.$division->getId(),
                'id' => (string) $division->getId(),
                'competition' => '/api/competitions/'.$division->getCompetition()->getId(),
                'name' => $division->getName(),
                'sourceName' => $division->getSourceName(),
                'externalId' => $division->getExternalId(),
                'sourceUrl' => $division->getSourceUrl(),
            ] : null,
            'workoutDetails' => $workout instanceof Workout ? [
                '@id' => '/api/workouts/'.$workout->getId(),
                'id' => (string) $workout->getId(),
                'name' => $workout->getName(),
                'flow' => $this->workoutFlow($workout),
                'sourceName' => $workout->getSourceName(),
                'externalId' => $workout->getExternalId(),
                'sourceUrl' => $workout->getSourceUrl(),
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function participationPayload(CompetitionParticipation $participation): array
    {
        $competition = $participation->getCompetition();

        return [
            '@id' => '/api/competition_participations/'.$participation->getId(),
            'id' => (string) $participation->getId(),
            'athlete' => '/api/athletes/'.$participation->getAthlete()->getId(),
            'competition' => '/api/competitions/'.$competition->getId(),
            'rank' => $participation->getRank(),
            'division' => $participation->getDivision(),
            'divisionSourceId' => $participation->getDivisionSourceId(),
            'format' => $participation->getFormat(),
            'formatSlug' => $participation->getFormatSlug(),
            'sourceName' => $participation->getSourceName(),
            'externalId' => $participation->getExternalId(),
            'sourceUrl' => $participation->getSourceUrl(),
            'createdAt' => $participation->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $participation->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            'competitionDetails' => [
                '@id' => '/api/competitions/'.$competition->getId(),
                'id' => (string) $competition->getId(),
                'name' => $competition->getName(),
                'season' => $competition->getSeason(),
                'sourceName' => $competition->getSourceName(),
                'externalId' => $competition->getExternalId(),
                'sourceUrl' => $competition->getSourceUrl(),
                'registrationUrl' => $competition->getRegistrationUrl(),
                'logoUrl' => $competition->getLogoUrl(),
                'officialQualifications' => $this->qualificationPresenter->confirmedPayload($competition),
                'startsAt' => $competition->getStartsAt()?->format(\DateTimeInterface::ATOM),
                'endsAt' => $competition->getEndsAt()?->format(\DateTimeInterface::ATOM),
                'locationLabel' => $competition->getLocationLabel(),
                'isOnline' => $competition->isOnline(),
                'competitionType' => $competition->getCompetitionType(),
                'participationType' => $competition->getParticipationType(),
            ],
        ];
    }

    private function workoutFlow(Workout $workout): ?string
    {
        $flow = trim($workout->getFlow());

        return in_array($flow, ['*', '-', '–', '—'], true) ? null : $flow;
    }

    /**
     * @return array{
     *     rank: ?int,
     *     fieldSize: ?int,
     *     label: ?string,
     *     rankPercent: ?float,
     *     percentile: ?float
     * }
     */
    private function rankContext(WorkoutResult $result): array
    {
        $rank = $result->getRank();
        $fieldSize = $result->getFieldSize();

        if ($rank === null || $fieldSize === null || $rank <= 0 || $fieldSize <= 0) {
            return [
                'rank' => $rank,
                'fieldSize' => $fieldSize,
                'label' => null,
                'rankPercent' => null,
                'percentile' => null,
            ];
        }

        return [
            'rank' => $rank,
            'fieldSize' => $fieldSize,
            'label' => sprintf('%d / %d', $rank, $fieldSize),
            'rankPercent' => round(($rank / $fieldSize) * 100, 2),
            'percentile' => round((($fieldSize - $rank) / $fieldSize) * 100, 2),
        ];
    }

    /**
     * @return list<Athlete>
     */
    private function relatedAthletes(
        Athlete $athlete,
        EntityManagerInterface $entityManager,
        AthleteNameNormalizer $athleteNameNormalizer,
    ): array {
        $normalizedNames = array_values(array_unique(array_filter([
            $athlete->getNormalizedName() ?: $athleteNameNormalizer->normalize($athlete->getDisplayName()),
            ...$this->reversedNameCandidates($athlete, $athleteNameNormalizer),
        ])));

        if ($normalizedNames === []) {
            return [$athlete];
        }

        /** @var list<Athlete> $athletes */
        $athletes = $entityManager->getRepository(Athlete::class)->createQueryBuilder('athlete')
            ->andWhere('athlete.normalizedName IN (:normalizedNames)')
            ->setParameter('normalizedNames', $normalizedNames)
            ->getQuery()
            ->getResult();

        $athletesById = [];
        foreach ([$athlete, ...$athletes] as $relatedAthlete) {
            $athletesById[(string) $relatedAthlete->getId()] = $relatedAthlete;
        }

        return array_values($athletesById);
    }

    /**
     * @return list<string>
     */
    private function reversedNameCandidates(Athlete $athlete, AthleteNameNormalizer $athleteNameNormalizer): array
    {
        $candidates = [];
        $firstName = $athlete->getFirstName();
        $lastName = $athlete->getLastName();

        if ($firstName !== null && $lastName !== null) {
            $candidates[] = $athleteNameNormalizer->normalize(sprintf('%s %s', $lastName, $firstName));
            $candidates[] = $athleteNameNormalizer->normalize(sprintf('%s %s', $firstName, $lastName));
        }

        $parts = explode(' ', $athlete->getNormalizedName() ?: $athleteNameNormalizer->normalize($athlete->getDisplayName()));
        if (count($parts) === 2) {
            $candidates[] = implode(' ', array_reverse($parts));
        }

        return $candidates;
    }
}
