<?php

namespace App\Services\Competition;

use App\Entity\Competition\Athlete;
use App\Entity\Competition\AthletePublicAnalysis;
use App\Entity\Competition\Competition;
use App\Entity\Competition\WorkoutResult;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class AthletePublicAnalysisGenerator
{
    public const MAX_AGE = 'P6M';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private HttpClientInterface $httpClient,
        private string $chatGPTApiKey,
        private string $openAiModel = 'gpt-4.1-mini',
    ) {
    }

    public function shouldGenerate(Athlete $athlete): bool
    {
        if (!$this->isGamesAthlete($athlete)) {
            return false;
        }

        $analysis = $this->existingAnalysis($athlete);

        return $analysis === null || $analysis->isOlderThan(new \DateInterval(self::MAX_AGE));
    }

    public function generateIfNeeded(Athlete $athlete): ?AthletePublicAnalysis
    {
        if (!$this->shouldGenerate($athlete)) {
            return $this->existingAnalysis($athlete);
        }

        return $this->generate($athlete);
    }

    public function generate(Athlete $athlete): ?AthletePublicAnalysis
    {
        $input = $this->analysisInput($athlete);
        if ($input === []) {
            return null;
        }

        $promptHash = $this->promptHash($athlete, $input);
        $analysis = $this->requestAnalysis($athlete, $input);
        $existing = $this->existingAnalysis($athlete);

        if ($existing instanceof AthletePublicAnalysis) {
            $existing->replace($promptHash, $analysis);
            $row = $existing;
        } else {
            $row = new AthletePublicAnalysis(
                $athlete,
                AthletePublicAnalysis::KIND_GAMES_PUBLIC,
                $promptHash,
                $analysis,
            );
            $this->entityManager->persist($row);
        }

        $this->entityManager->flush();

        return $row;
    }

    public function isGamesAthlete(Athlete $athlete): bool
    {
        $count = $this->entityManager->createQueryBuilder()
            ->select('COUNT(result.id)')
            ->from(WorkoutResult::class, 'result')
            ->join('result.event', 'event')
            ->join('event.competition', 'competition')
            ->where('result.athlete = :athlete')
            ->andWhere('competition.sourceName = :sourceName')
            ->andWhere('LOWER(competition.name) LIKE :games')
            ->andWhere('LOWER(competition.name) NOT LIKE :open')
            ->setParameter('athlete', $athlete)
            ->setParameter('sourceName', 'crossfit_games')
            ->setParameter('games', '%games%')
            ->setParameter('open', '%open%')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    /**
     * @return list<Athlete>
     */
    public function eligibleAthletes(int $limit = 25): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('DISTINCT athlete')
            ->from(Athlete::class, 'athlete')
            ->join(WorkoutResult::class, 'result', 'WITH', 'result.athlete = athlete')
            ->join('result.event', 'event')
            ->join('event.competition', 'competition')
            ->where('competition.sourceName = :sourceName')
            ->andWhere('LOWER(competition.name) LIKE :games')
            ->andWhere('LOWER(competition.name) NOT LIKE :open')
            ->orderBy('athlete.displayName', 'ASC')
            ->setMaxResults($limit)
            ->setParameter('sourceName', 'crossfit_games')
            ->setParameter('games', '%games%')
            ->setParameter('open', '%open%')
            ->getQuery()
            ->getResult();
    }

    private function existingAnalysis(Athlete $athlete): ?AthletePublicAnalysis
    {
        return $this->entityManager->getRepository(AthletePublicAnalysis::class)->findOneBy([
            'athlete' => $athlete,
            'kind' => AthletePublicAnalysis::KIND_GAMES_PUBLIC,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function analysisInput(Athlete $athlete): array
    {
        $results = $this->entityManager->createQueryBuilder()
            ->select('result', 'event', 'competition', 'score', 'workout', 'division')
            ->from(WorkoutResult::class, 'result')
            ->join('result.event', 'event')
            ->join('event.competition', 'competition')
            ->join('result.score', 'score')
            ->leftJoin('event.workout', 'workout')
            ->leftJoin('result.competitionDivision', 'division')
            ->where('result.athlete = :athlete')
            ->andWhere('competition.sourceName = :sourceName')
            ->orderBy('competition.season', 'ASC')
            ->addOrderBy('event.eventOrder', 'ASC')
            ->setParameter('athlete', $athlete)
            ->setParameter('sourceName', 'crossfit_games')
            ->getQuery()
            ->getResult();

        $workoutResults = array_values(array_filter(
            $results,
            static fn ($result): bool => $result instanceof WorkoutResult,
        ));

        $input = [];
        foreach ($this->filterNonAttemptedQualificationResults($workoutResults) as $result) {
            if (!$result instanceof WorkoutResult) {
                continue;
            }

            $competition = $result->getEvent()->getCompetition();
            if (!$this->isRelevantCrossFitStage($competition)) {
                continue;
            }

            $workout = $result->getEvent()->getWorkout();
            $flow = $workout?->getFlow();
            if ($flow === null || trim($flow) === '') {
                continue;
            }

            $input[] = [
                'competition' => $competition->getName(),
                'season' => $competition->getSeason(),
                'event' => $result->getEvent()->getName(),
                'division' => $result->getCompetitionDivision()?->getName() ?? $result->getDivision(),
                'rank' => $result->getRank(),
                'score' => $result->getScore()->getDisplayValue() ?? $result->getScore()->getRawValue(),
                'workout' => [
                    'name' => $workout->getName(),
                    'flow' => $flow,
                    'movements' => array_map(
                        static fn ($movement): ?string => method_exists($movement, 'getName') ? $movement->getName() : null,
                        $workout->getMovements()->toArray(),
                    ),
                    'implements' => array_map(
                        static fn ($implement): ?string => method_exists($implement, 'getName') ? $implement->getName() : null,
                        $workout->getImplements()->toArray(),
                    ),
                ],
            ];
        }

        return array_slice($input, -80);
    }

    /**
     * Removes qualification/Open series that look like ghost registrations: the
     * athlete is ranked last on every event and every score is empty/zero/DNS.
     *
     * @param list<WorkoutResult> $results
     *
     * @return list<WorkoutResult>
     */
    private function filterNonAttemptedQualificationResults(array $results): array
    {
        $groups = [];
        foreach ($results as $index => $result) {
            $competition = $result->getEvent()->getCompetition();
            if (!$this->isQualificationLikeCompetition($competition)) {
                continue;
            }

            $groups[$this->analysisCompetitionGroupKey($result)][] = $index;
        }

        $excludedIndexes = [];
        $maxRankCache = [];
        foreach ($groups as $indexes) {
            if (count($indexes) < 2) {
                continue;
            }

            foreach ($indexes as $index) {
                $result = $results[$index];
                if (!$this->isLastRankInEventDivision($result, $maxRankCache) || $this->hasMeaningfulScore($result)) {
                    continue 2;
                }
            }

            foreach ($indexes as $index) {
                $excludedIndexes[$index] = true;
            }
        }

        if ($excludedIndexes === []) {
            return $results;
        }

        return array_values(array_filter(
            $results,
            static fn (WorkoutResult $result, int $index): bool => !isset($excludedIndexes[$index]),
            ARRAY_FILTER_USE_BOTH,
        ));
    }

    private function isRelevantCrossFitStage(Competition $competition): bool
    {
        $name = strtolower($competition->getName());

        return str_contains($name, 'games')
            || str_contains($name, 'semifinal')
            || str_contains($name, 'regional')
            || str_contains($name, 'quarterfinal')
            || str_contains($name, 'open');
    }

    private function isQualificationLikeCompetition(Competition $competition): bool
    {
        $name = strtolower($competition->getName());

        return str_contains($name, 'open')
            || str_contains($name, 'qualif')
            || str_contains($name, 'qualifying');
    }

    private function analysisCompetitionGroupKey(WorkoutResult $result): string
    {
        $competition = $result->getEvent()->getCompetition();
        $competitionKey = $competition->getSourceName().':'.$competition->getExternalId();
        $divisionKey = $result->getDivisionSourceId()
            ?? $result->getCompetitionDivision()?->getExternalId()
            ?? strtolower((string) $result->getDivision());

        return $competitionKey.':'.$divisionKey;
    }

    /**
     * @param array<string, int|null> $maxRankCache
     */
    private function isLastRankInEventDivision(WorkoutResult $result, array &$maxRankCache): bool
    {
        $rank = $result->getRank();
        if ($rank === null) {
            return false;
        }

        $fieldSize = $result->getFieldSize();
        if ($fieldSize !== null) {
            return $rank >= $fieldSize;
        }

        $cacheKey = implode(':', [
            $result->getEvent()->getSourceName(),
            $result->getEvent()->getExternalId(),
            $result->getDivisionSourceId() ?? '',
            $result->getCompetitionDivision()?->getExternalId() ?? '',
            strtolower((string) $result->getDivision()),
        ]);

        if (!array_key_exists($cacheKey, $maxRankCache)) {
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
            $maxRankCache[$cacheKey] = $maxRank !== null ? (int) $maxRank : null;
        }

        return $maxRankCache[$cacheKey] !== null && $rank >= $maxRankCache[$cacheKey];
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

        if (preg_match('/^0(?:[.,]0+)?\s*(?:reps?|rounds?|pts?|points?|lb|lbs|kg|cal|cals|m|meters?)$/', $value) === 1) {
            return false;
        }

        return true;
    }

    /**
     * @param list<array<string, mixed>> $input
     *
     * @return array<string, mixed>
     */
    private function requestAnalysis(Athlete $athlete, array $input): array
    {
        if (trim($this->chatGPTApiKey) === '') {
            throw new \RuntimeException('CHAT_GPT_API_KEY is required to generate athlete public analyses.');
        }

        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->chatGPTApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $this->openAiModel,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es un coach CrossFit et analyste de performance. Analyse uniquement les donnees fournies, distingue les faits des hypotheses, et retourne uniquement un JSON valide en francais.',
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode([
                            'task' => 'Produire une analyse publique courte pour teaser une analyse personnelle MonWOD.',
                            'athlete' => $athlete->getDisplayName(),
                            'results' => $input,
                            'expected_schema' => [
                                'summary' => 'string',
                                'strengths' => ['string'],
                                'weaknesses' => ['string'],
                                'eventProfile' => ['string'],
                                'trainingPriorities' => ['string'],
                                'conclusion' => 'string',
                            ],
                        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    ],
                ],
            ],
        ]);

        try {
            $data = $response->toArray();
        } catch (
            ClientExceptionInterface
            |DecodingExceptionInterface
            |RedirectionExceptionInterface
            |ServerExceptionInterface
            |TransportExceptionInterface $exception
        ) {
            throw new \RuntimeException('OpenAI analysis request failed: '.$exception->getMessage(), 0, $exception);
        }

        $content = $data['choices'][0]['message']['content'] ?? '{}';
        $analysis = json_decode((string) $content, true);

        if (!is_array($analysis)) {
            throw new \RuntimeException('OpenAI analysis response is not a JSON object.');
        }

        return [
            'kind' => AthletePublicAnalysis::KIND_GAMES_PUBLIC,
            'model' => $this->openAiModel,
            ...$analysis,
        ];
    }

    /**
     * @param list<array<string, mixed>> $input
     */
    private function promptHash(Athlete $athlete, array $input): string
    {
        return hash('sha256', json_encode([
            'kind' => AthletePublicAnalysis::KIND_GAMES_PUBLIC,
            'athlete' => $athlete->getDisplayName(),
            'input' => $input,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }
}
