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
        $entityManager->persist(
            (new Athlete('Tia-Clair Toomey', 'crossfit_games', 'tia-toomey'))
                ->setAvatarUrl('https://profilepicsbucket.crossfit.com/tia.jpg')
        );
        $entityManager->persist(new Athlete('Mat Fraser', 'crossfit_games', 'mat-fraser'));
        $entityManager->flush();

        $this->browser()->request('GET', '/api/athletes?displayName=tia');

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $athletes = $payload['member'] ?? $payload['hydra:member'] ?? [];
        $names = array_map(static fn (array $athlete): ?string => $athlete['displayName'] ?? null, $athletes);

        self::assertContains('Tia-Clair Toomey', $names);
        self::assertNotContains('Mat Fraser', $names);
        self::assertSame('https://profilepicsbucket.crossfit.com/tia.jpg', $athletes[0]['avatarUrl']);
    }

    public function testFrontendListsLatestEliteGamesAthletesFirst(): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist(
            new Athlete('Alphabetical Athlete', 'competition_corner', 'local-athlete')
        );
        $entityManager->persist(
            (new Athlete('Former Champion', 'crossfit_games', 'former-champion'))
                ->setEliteGamesSeason(2023)
                ->setEliteGamesRank(1)
        );
        $entityManager->persist(
            (new Athlete('Recent Runner Up', 'crossfit_games', 'recent-runner-up'))
                ->setEliteGamesSeason(2025)
                ->setEliteGamesRank(2)
        );
        $entityManager->persist(
            (new Athlete('Recent Champion', 'crossfit_games', 'recent-champion'))
                ->setEliteGamesSeason(2025)
                ->setEliteGamesRank(1)
        );
        $entityManager->flush();

        $this->browser()->request('GET', '/api/athletes');

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $athletes = $payload['member'] ?? $payload['hydra:member'] ?? [];
        $names = array_map(static fn (array $athlete): ?string => $athlete['displayName'] ?? null, $athletes);

        self::assertSame(
            ['Recent Champion', 'Recent Runner Up', 'Former Champion', 'Alphabetical Athlete'],
            array_slice($names, 0, 4),
        );
        self::assertSame(1, $athletes[0]['eliteGamesRank']);
        self::assertSame(2025, $athletes[0]['eliteGamesSeason']);
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
            ->setRank(1)
            ->setFieldSize(40);
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
        self::assertSame(40, $results[0]['fieldSize']);
    }

    public function testFrontendCanReadCompactAthleteResultSummary(): void
    {
        $entityManager = $this->getEntityManager();
        /** @var Workout $workout */
        $workout = $this->getReference(WorkoutData::WORKOUT_OPEN_17_5, Workout::class);
        $gamesProfile = new Athlete('Sabrina Caron', 'crossfit_games', 'sabrina-games');
        $cornerProfile = new Athlete('Sabrina Caron', 'competition_corner', 'sabrina-corner');
        $otherAthlete = new Athlete('Other Athlete', 'crossfit_games', 'other-athlete');
        $competition = (new Competition('2019 Games', 'crossfit_games', 'games-2019'))
            ->setSeason(2019);
        $event = (new CompetitionEvent($competition, 'Event 1', 'crossfit_games', 'games-2019-event-1'))
            ->setWorkout($workout);
        $division = new CompetitionDivision($competition, 'Women', 'crossfit_games', 'games-2019-women');
        $gamesResult = (new WorkoutResult($gamesProfile, $event, new Score(ScoreTypeEnum::TIME, '8:21'), 'crossfit_games', 'sabrina-games-event-1'))
            ->setCompetitionDivision($division)
            ->setRank(12)
            ->setFieldSize(40);
        $cornerResult = (new WorkoutResult($cornerProfile, $event, new Score(ScoreTypeEnum::REPS, '127'), 'competition_corner', 'sabrina-corner-event-1'))
            ->setCompetitionDivision($division)
            ->setRank(2)
            ->setFieldSize(18);
        $otherResult = new WorkoutResult($otherAthlete, $event, new Score(ScoreTypeEnum::TIME, '7:55'), 'crossfit_games', 'other-event-1');

        foreach ([
            $gamesProfile,
            $cornerProfile,
            $otherAthlete,
            $competition,
            $event,
            $division,
            $gamesResult,
            $cornerResult,
            $otherResult,
        ] as $entity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();
        $entityManager->clear();

        $this->browser()->request('GET', sprintf('/api/athletes/%s/result-summary', $gamesProfile->getId()));

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(2, $payload['totalItems']);
        self::assertCount(2, $payload['member']);
        self::assertSame('2019 Games', $payload['member'][0]['competitionDetails']['name']);
        self::assertSame('Event 1', $payload['member'][0]['eventDetails']['name']);
        self::assertSame('17.5', $payload['member'][0]['workoutDetails']['name']);
        self::assertArrayHasKey('scoreDetails', $payload['member'][0]);
        self::assertContains('/api/athletes/'.$cornerProfile->getId(), array_column($payload['member'], 'athlete'));
        self::assertNotContains('/api/athletes/'.$otherAthlete->getId(), array_column($payload['member'], 'athlete'));
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
