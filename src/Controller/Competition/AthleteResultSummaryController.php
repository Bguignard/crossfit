<?php

namespace App\Controller\Competition;

use App\Entity\Competition\Athlete;
use App\Entity\Competition\WorkoutResult;
use App\Entity\Workout\Workout;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class AthleteResultSummaryController extends AbstractController
{
    #[Route('/api/athletes/{id}/result-summary', name: 'api_athlete_result_summary', methods: ['GET'])]
    public function __invoke(string $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $athlete = $entityManager->getRepository(Athlete::class)->find($id);

        if (!$athlete instanceof Athlete) {
            throw new NotFoundHttpException('Athlete not found.');
        }

        $relatedAthletes = $entityManager->getRepository(Athlete::class)->findBy([
            'displayName' => $athlete->getDisplayName(),
        ]);

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

        return $this->json([
            'totalItems' => count($results),
            'member' => array_map([$this, 'resultPayload'], $results),
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
            'division' => $result->getDivision(),
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
                'sourceName' => $event->getSourceName(),
                'externalId' => $event->getExternalId(),
                'sourceUrl' => $event->getSourceUrl(),
            ],
            'competitionDetails' => [
                '@id' => '/api/competitions/'.$competition->getId(),
                'id' => (string) $competition->getId(),
                'name' => $competition->getName(),
                'season' => $competition->getSeason(),
                'sourceName' => $competition->getSourceName(),
                'externalId' => $competition->getExternalId(),
                'sourceUrl' => $competition->getSourceUrl(),
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
                'flow' => $workout->getFlow(),
                'sourceName' => $workout->getSourceName(),
                'externalId' => $workout->getExternalId(),
                'sourceUrl' => $workout->getSourceUrl(),
            ] : null,
        ];
    }
}
