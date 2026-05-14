<?php

namespace App\Tests;

use App\DataFixtures\WorkoutData;
use App\Entity\Competition\Athlete;
use App\Entity\Competition\AthletePublicAnalysis;
use App\Entity\Competition\Competition;
use App\Entity\Competition\CompetitionDivision;
use App\Entity\Competition\CompetitionEvent;
use App\Entity\Competition\Enum\ScoreTypeEnum;
use App\Entity\Competition\Score;
use App\Entity\Competition\WorkoutResult;
use App\Entity\Workout\Workout;

class WorkoutApiWorkflowTest extends AbstractIntegrationTest
{
    public function testFrontendCanListWorkoutCatalogFromApi(): void
    {
        $this->browser()->request('GET', '/api/workouts?itemsPerPage=1000');

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $workouts = $payload['member'] ?? $payload['hydra:member'] ?? [];
        $names = array_map(static fn (array $workout): ?string => $workout['name'] ?? null, $workouts);

        self::assertContains('Fran', $names);
        self::assertLessThanOrEqual(50, count($workouts));
    }

    public function testFrontendCanReadWorkoutCompetitionContext(): void
    {
        $entityManager = $this->getEntityManager();
        /** @var Workout $workout */
        $workout = $this->getReference(WorkoutData::WORKOUT_OPEN_17_5, Workout::class);
        $workoutId = (string) $workout->getId();
        $athlete = new Athlete('Tia-Clair Toomey', 'crossfit_games', 'tia-context');
        $competition = (new Competition('CrossFit Games Open', 'crossfit_games', 'open-2017'))
            ->setSeason(2017);
        $event = (new CompetitionEvent($competition, 'Open 17.5', 'crossfit_games', 'open-2017-17-5'))
            ->setEventOrder(5)
            ->setWorkout($workout);
        $division = new CompetitionDivision($competition, 'Women', 'crossfit_games', 'open-2017-women');
        $result = (new WorkoutResult($athlete, $event, new Score(ScoreTypeEnum::TIME, '10:21'), 'crossfit_games', 'open-2017-tia'))
            ->setCompetitionDivision($division)
            ->setDivision('Women')
            ->setRank(1);

        foreach ([$athlete, $competition, $event, $division, $result] as $entity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();
        $entityManager->clear();

        $this->browser()->request('GET', sprintf('/api/workouts/%s', $workoutId));

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame([
            [
                'competitionName' => 'CrossFit Games Open',
                'competitionSeason' => 2017,
                'eventName' => 'Open 17.5',
                'eventOrder' => 5,
                'sourceName' => 'crossfit_games',
                'divisions' => ['Women'],
            ],
        ], $payload['competitionContexts']);
    }

    public function testFrontendCanListPublicAthletesEvenWhenCatalogIsEmpty(): void
    {
        $this->browser()->request('GET', '/api/athletes');

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $athletes = $payload['member'] ?? $payload['hydra:member'] ?? null;

        self::assertIsArray($athletes);
    }

    public function testFrontendCanSearchAthletesByDisplayName(): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist(new Athlete('Tia-Clair Toomey', 'crossfit_games', 'tia-toomey'));
        $entityManager->persist(new Athlete('Mat Fraser', 'crossfit_games', 'mat-fraser'));
        $entityManager->flush();

        $this->browser()->request('GET', '/api/athletes?displayName=tia');

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $athletes = $payload['member'] ?? $payload['hydra:member'] ?? [];
        $names = array_map(static fn (array $athlete): ?string => $athlete['displayName'] ?? null, $athletes);

        self::assertContains('Tia-Clair Toomey', $names);
        self::assertNotContains('Mat Fraser', $names);
    }

    public function testFrontendCanReadStoredPublicAthleteAnalysis(): void
    {
        $entityManager = $this->getEntityManager();
        $athlete = new Athlete('Tia-Clair Toomey', 'crossfit_games', 'tia-analysis');
        $analysis = new AthletePublicAnalysis($athlete, AthletePublicAnalysis::KIND_GAMES_PUBLIC, 'hash', [
            'summary' => 'Dominant Games profile.',
            'strengths' => ['Engine under fatigue'],
            'weaknesses' => ['Limited weaknesses in imported data'],
            'eventProfile' => ['Strong across mixed modal events'],
            'trainingPriorities' => ['Keep high-skill volume healthy'],
            'conclusion' => 'A benchmark athlete for public teaser analysis.',
            'model' => 'test-model',
        ]);

        $entityManager->persist($athlete);
        $entityManager->persist($analysis);
        $entityManager->flush();
        $entityManager->clear();

        $this->browser()->request('GET', sprintf('/api/athletes/%s', $athlete->getId()));

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('Dominant Games profile.', $payload['publicAnalysis']['summary']);
        self::assertSame(['Engine under fatigue'], $payload['publicAnalysis']['strengths']);
        self::assertArrayHasKey('generatedAt', $payload['publicAnalysis']);
    }

    public function testFrontendCanFilterWorkoutResultsByAthleteIri(): void
    {
        $entityManager = $this->getEntityManager();
        $tia = new Athlete('Tia-Clair Toomey', 'crossfit_games', 'tia-toomey-results');
        $mat = new Athlete('Mat Fraser', 'crossfit_games', 'mat-fraser-results');
        $competition = (new Competition('CrossFit Games', 'crossfit_games', 'games-2017'))
            ->setSeason(2017);
        $event = new CompetitionEvent($competition, '17.5', 'crossfit_games', 'games-2017-17-5');
        $division = new CompetitionDivision($competition, 'Women', 'crossfit_games', 'games-2017-women');
        $tiaResult = (new WorkoutResult($tia, $event, new Score(ScoreTypeEnum::TIME, '6:35'), 'crossfit_games', 'tia-17-5'))
            ->setCompetitionDivision($division)
            ->setRank(1);
        $matResult = (new WorkoutResult($mat, $event, new Score(ScoreTypeEnum::TIME, '6:24'), 'crossfit_games', 'mat-17-5'))
            ->setRank(1);

        foreach ([$tia, $mat, $competition, $event, $division, $tiaResult, $matResult] as $entity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();

        $this->browser()->request('GET', sprintf('/api/workout_results?athlete=/api/athletes/%s', $tia->getId()));

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $results = $payload['member'] ?? $payload['hydra:member'] ?? [];

        self::assertCount(1, $results);
        self::assertSame('/api/athletes/'.$tia->getId(), $results[0]['athlete']);
        self::assertSame(1, $results[0]['rank']);
    }

    public function testPublicWorkoutCatalogIsReadOnly(): void
    {
        $this->browser()->request(
            'POST',
            '/api/workouts',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Should not be created'], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(405);
    }

    public function testWorkoutGenerationIsNotPubliclyListedOrTriggeredByGet(): void
    {
        $this->browser()->request('GET', '/api/workout_generations');

        self::assertResponseStatusCodeSame(404);

        $this->browser()->request('GET', '/api/workout-generator/00000000-0000-0000-0000-000000000000');

        self::assertResponseStatusCodeSame(405);
    }
}
