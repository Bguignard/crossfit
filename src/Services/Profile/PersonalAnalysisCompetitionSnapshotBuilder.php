<?php

namespace App\Services\Profile;

use App\Entity\Competition\Competition;
use App\Entity\Competition\WorkoutResult;
use App\Entity\Product\UserAthleteProfile;
use Doctrine\ORM\EntityManagerInterface;

final class PersonalAnalysisCompetitionSnapshotBuilder
{
    private const int MAX_RESULTS = 80;
    private const array ANALYSABLE_SOURCES = ['crossfit_games', 'competition_corner'];

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @return array{
     *     competition_results: list<array<string, mixed>>,
     *     excluded_non_attempted_results: list<array<string, mixed>>
     * }
     */
    public function build(?UserAthleteProfile $athleteProfile): array
    {
        if ($athleteProfile === null) {
            return $this->buildMany([]);
        }

        return $this->buildMany([$athleteProfile]);
    }

    /**
     * @param iterable<UserAthleteProfile> $athleteProfiles
     *
     * @return array{
     *     competition_results: list<array<string, mixed>>,
     *     excluded_non_attempted_results: list<array<string, mixed>>
     * }
     */
    public function buildMany(iterable $athleteProfiles): array
    {
        $profiles = [];
        foreach ($athleteProfiles as $athleteProfile) {
            if (!$this->isAnalysableProfile($athleteProfile)) {
                continue;
            }
            $profiles[] = $athleteProfile;
        }

        if ($profiles === []) {
            return [
                'competition_results' => [],
                'excluded_non_attempted_results' => [],
            ];
        }

        $included = [];
        $excluded = [];

        foreach ($profiles as $athleteProfile) {
            foreach ($this->workoutResults($athleteProfile) as $result) {
                if ($this->isNonAttemptedQualificationResult($result)) {
                    $excluded[] = $this->resultPayload($athleteProfile, $result, 'non_attempted_or_not_submitted');
                    continue;
                }

                $included[] = $this->resultPayload($athleteProfile, $result);
            }
        }

        return [
            'competition_results' => array_slice($included, -self::MAX_RESULTS),
            'excluded_non_attempted_results' => array_slice($excluded, -self::MAX_RESULTS),
        ];
    }

    /**
     * @return list<WorkoutResult>
     */
    private function workoutResults(UserAthleteProfile $athleteProfile): array
    {
        $results = $this->entityManager->createQueryBuilder()
            ->select('result', 'event', 'competition', 'score', 'division')
            ->from(WorkoutResult::class, 'result')
            ->join('result.event', 'event')
            ->join('event.competition', 'competition')
            ->join('result.score', 'score')
            ->leftJoin('result.competitionDivision', 'division')
            ->where('result.athlete = :athlete')
            ->orderBy('competition.season', 'ASC')
            ->addOrderBy('competition.startsAt', 'ASC')
            ->addOrderBy('event.eventOrder', 'ASC')
            ->addOrderBy('event.name', 'ASC')
            ->setParameter('athlete', $athleteProfile->getAthlete())
            ->getQuery()
            ->getResult();

        return array_values(array_filter(
            $results,
            static fn (mixed $result): bool => $result instanceof WorkoutResult,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function resultPayload(UserAthleteProfile $athleteProfile, WorkoutResult $result, ?string $excludedReason = null): array
    {
        $event = $result->getEvent();
        $competition = $event->getCompetition();
        $score = $result->getScore();

        $payload = [
            'athlete_profile_id' => (string) $athleteProfile->getId(),
            'athlete_id' => (string) $athleteProfile->getAthlete()->getId(),
            'athlete_display_name' => $athleteProfile->getAthlete()->getDisplayName(),
            'athlete_source_name' => $athleteProfile->getAthlete()->getSourceName(),
            'athlete_external_id' => $athleteProfile->getAthlete()->getExternalId(),
            'competition' => $competition->getName(),
            'competition_source' => $competition->getSourceName(),
            'competition_external_id' => $competition->getExternalId(),
            'season' => $competition->getSeason(),
            'event' => $event->getName(),
            'division' => $result->getCompetitionDivision()?->getName() ?? $result->getDivision(),
            'rank' => $result->getRank(),
            'field_size' => $result->getFieldSize(),
            'score' => $score->getDisplayValue() ?? $score->getRawValue(),
            'score_type' => $score->getType()->value,
            'numeric_value' => $score->getNumericValue(),
            'time_in_seconds' => $score->getTimeInSeconds(),
        ];

        if ($excludedReason !== null) {
            $payload['excluded_reason'] = $excludedReason;
        }

        return $payload;
    }

    private function isAnalysableProfile(UserAthleteProfile $athleteProfile): bool
    {
        return $athleteProfile->getLinkType() === UserAthleteProfile::LINK_SELF
            && in_array($athleteProfile->getAthlete()->getSourceName(), self::ANALYSABLE_SOURCES, true);
    }

    private function isNonAttemptedQualificationResult(WorkoutResult $result): bool
    {
        return $this->isQualificationLikeCompetition($result->getEvent()->getCompetition())
            && $this->isLastRankInEventDivision($result)
            && !$this->hasMeaningfulScore($result);
    }

    private function isQualificationLikeCompetition(Competition $competition): bool
    {
        $name = strtolower($competition->getName());
        $type = strtolower((string) $competition->getCompetitionType());

        return str_contains($name, 'open')
            || str_contains($name, 'qualif')
            || str_contains($name, 'qualifying')
            || str_contains($type, 'qualif')
            || str_contains($type, 'qualifying');
    }

    private function isLastRankInEventDivision(WorkoutResult $result): bool
    {
        $rank = $result->getRank();
        if ($rank === null) {
            return false;
        }

        $fieldSize = $result->getFieldSize();
        if ($fieldSize !== null) {
            return $rank >= $fieldSize;
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('MAX(peer.rank)')
            ->from(WorkoutResult::class, 'peer')
            ->where('peer.event = :event')
            ->andWhere('peer.rank IS NOT NULL')
            ->setParameter('event', $result->getEvent());

        if ($result->getDivisionSourceId() !== null) {
            $queryBuilder
                ->andWhere('peer.divisionSourceId = :divisionSourceId')
                ->setParameter('divisionSourceId', $result->getDivisionSourceId());
        } elseif ($result->getCompetitionDivision() !== null) {
            $queryBuilder
                ->andWhere('peer.competitionDivision = :division')
                ->setParameter('division', $result->getCompetitionDivision());
        } elseif ($result->getDivision() !== null) {
            $queryBuilder
                ->andWhere('LOWER(peer.division) = :division')
                ->setParameter('division', strtolower($result->getDivision()));
        }

        $maxRank = $queryBuilder->getQuery()->getSingleScalarResult();

        return $maxRank !== null && $rank >= (int) $maxRank;
    }

    private function hasMeaningfulScore(WorkoutResult $result): bool
    {
        $score = $result->getScore();
        if (($score->getNumericValue() ?? 0.0) > 0.0 || ($score->getTimeInSeconds() ?? 0) > 0) {
            return true;
        }

        $value = strtolower(trim((string) ($score->getDisplayValue() ?? $score->getRawValue())));
        if ($value === '') {
            return false;
        }

        $nonMeaningfulValues = [
            '-',
            '--',
            '0',
            '0.0',
            '0:00',
            '00:00',
            '0 reps',
            '0 rep',
            '0 rounds',
            '0 points',
            '0 pts',
            '0 lb',
            '0 lbs',
            '0 kg',
            'dnf',
            'dns',
            'no score',
            'not submitted',
            'did not finish',
            'did not start',
        ];

        if (in_array($value, $nonMeaningfulValues, true)) {
            return false;
        }

        return preg_match('/^0(?:[.,]0+)?\s*(?:reps?|rounds?|pts?|points?|lb|lbs|kg|cal|cals|m|meters?)$/', $value) !== 1;
    }
}
