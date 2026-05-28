<?php

namespace App\Tests;

use App\DataFixtures\MovementData;
use App\DataFixtures\WorkoutData;
use App\Entity\Competition\Athlete;
use App\Entity\Competition\AthletePublicAnalysis;
use App\Entity\Competition\Competition;
use App\Entity\Competition\CompetitionDivision;
use App\Entity\Competition\CompetitionEvent;
use App\Entity\Competition\Enum\ScoreTypeEnum;
use App\Entity\Competition\Score;
use App\Entity\Competition\WorkoutResult;
use App\Entity\Security\User;
use App\Entity\Workout\BodyPart;
use App\Entity\Workout\Enum\BodyPartEnum;
use App\Entity\Workout\Enum\ImplementEnum;
use App\Entity\Workout\Enum\MovementDifficultyEnum;
use App\Entity\Workout\Enum\MovementTypeEnum;
use App\Entity\Workout\Enum\WorkoutMovementGenerationTypeEnum;
use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use App\Entity\Workout\Enum\WorkoutTypeEnum;
use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\MovementDifficulty;
use App\Entity\Workout\MovementType;
use App\Entity\Workout\Workout;
use App\Entity\Workout\WorkoutMovementGenerationType;
use App\Entity\Workout\WorkoutOrigin;
use App\Entity\Workout\WorkoutOriginName;
use App\Entity\Workout\WorkoutType;
use App\Entity\WorkoutGeneration\WorkoutGeneration;
use App\Services\Workout\WorkoutCreatorServiceInterface;

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
                'competitionLogoUrl' => null,
                'eventName' => 'Open 17.5',
                'eventOrder' => 5,
                'sourceName' => 'crossfit_games',
                'divisions' => ['Women'],
            ],
        ], $payload['competitionContexts']);
    }

    public function testFrontendCanReadPaginatedCompetitionCatalogWithoutFetchingEverything(): void
    {
        $entityManager = $this->getEntityManager();
        $now = new \DateTimeImmutable();
        $franceUpcoming = (new Competition('French Throwdown 2026', 'competition_corner', 'fast-catalog-france'))
            ->setStartsAt($now->modify('+1 month'))
            ->setLocationLabel('Paris, France')
            ->setCompetitionType('functional_fitness')
            ->setParticipationType('individual')
            ->setLogoUrl('https://example.test/french.png');
        $usaUpcoming = (new Competition('Granite Games 2026', 'competition_corner', 'fast-catalog-usa'))
            ->setStartsAt($now->modify('+2 months'))
            ->setLocationLabel('Minnesota, United States')
            ->setParticipationType('team');
        $pastOpen = (new Competition('CrossFit Open 2024', 'crossfit_games', 'fast-catalog-open'))
            ->setSeason(2024)
            ->setStatus('past')
            ->setLocationLabel('En ligne');

        foreach ([$franceUpcoming, $usaUpcoming, $pastOpen] as $competition) {
            $entityManager->persist($competition);
        }
        $entityManager->flush();

        $this->browser()->request('GET', '/api/competition-catalog?page=1&country=France&status=upcoming&source=competition_corner&participation=individual');

        self::assertResponseIsSuccessful();
        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(1, $payload['totalItems']);
        self::assertSame('French Throwdown 2026', $payload['member'][0]['name']);
        self::assertSame('https://example.test/french.png', $payload['member'][0]['logoUrl']);
        self::assertContains('France', $payload['countries']);
        self::assertContains('United States', $payload['countries']);
        self::assertNull($payload['view']['next']);
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

    public function testFrontendCanSearchAthletesWithAccentInsensitiveNormalizedNames(): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist(new Athlete('Oceane Garat', 'crossfit_games', 'oceane-games'));
        $entityManager->persist(new Athlete('Océane Garat', 'scoring_fit', 'oceane-scoring-fit'));
        $entityManager->persist(new Athlete('Ocean Other', 'crossfit_games', 'ocean-other'));
        $entityManager->flush();

        $this->browser()->request('GET', '/api/athletes?normalizedName=oceane%20garat');

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $athletes = $payload['member'] ?? $payload['hydra:member'] ?? [];
        $names = array_map(static fn (array $athlete): ?string => $athlete['displayName'] ?? null, $athletes);

        self::assertContains('Oceane Garat', $names);
        self::assertContains('Océane Garat', $names);
        self::assertNotContains('Ocean Other', $names);
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

    public function testFrontendCanRequestExistingPublicAthleteAnalysis(): void
    {
        $entityManager = $this->getEntityManager();
        $athlete = new Athlete('Tia-Clair Toomey', 'crossfit_games', 'tia-analysis-request');
        $analysis = new AthletePublicAnalysis($athlete, AthletePublicAnalysis::KIND_GAMES_PUBLIC, 'hash', [
            'summary' => 'Existing Games profile.',
            'strengths' => ['Complete athlete'],
            'model' => 'test-model',
        ]);

        $entityManager->persist($athlete);
        $entityManager->persist($analysis);
        $entityManager->flush();
        $entityManager->clear();

        $this->browser()->request('POST', sprintf('/api/athletes/%s/public-analysis', $athlete->getId()));

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($payload['eligible']);
        self::assertSame('Existing Games profile.', $payload['analysis']['summary']);
        self::assertSame(['Complete athlete'], $payload['analysis']['strengths']);
    }

    public function testFrontendDoesNotGeneratePublicAnalysisForNonGamesAthlete(): void
    {
        $entityManager = $this->getEntityManager();
        $athlete = new Athlete('Local Athlete', 'competition_corner', 'local-athlete-analysis');

        $entityManager->persist($athlete);
        $entityManager->flush();
        $entityManager->clear();

        $this->browser()->request('POST', sprintf('/api/athletes/%s/public-analysis', $athlete->getId()));

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertFalse($payload['eligible']);
        self::assertNull($payload['analysis']);
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
        $gamesProfile = new Athlete('Oceane Garat', 'crossfit_games', 'oceane-games');
        $cornerProfile = new Athlete('Océane Garat', 'competition_corner', 'oceane-corner');
        $otherAthlete = new Athlete('Other Athlete', 'crossfit_games', 'other-athlete');
        $competition = (new Competition('2019 Games', 'crossfit_games', 'games-2019'))
            ->setSeason(2019);
        $event = (new CompetitionEvent($competition, 'Event 1', 'crossfit_games', 'games-2019-event-1'))
            ->setWorkout($workout);
        $division = new CompetitionDivision($competition, 'Women', 'crossfit_games', 'games-2019-women');
        $gamesResult = (new WorkoutResult($gamesProfile, $event, new Score(ScoreTypeEnum::TIME, '8:21'), 'crossfit_games', 'oceane-games-event-1'))
            ->setCompetitionDivision($division)
            ->setRank(12)
            ->setFieldSize(40)
            ->setDivisionSourceId('women')
            ->setCompetitionRank('8')
            ->setCompetitionFormat('Individual')
            ->setCompetitionFormatSlug('individual');
        $cornerResult = (new WorkoutResult($cornerProfile, $event, new Score(ScoreTypeEnum::REPS, '127'), 'competition_corner', 'oceane-corner-event-1'))
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
        self::assertArrayHasKey('logoUrl', $payload['member'][0]['competitionDetails']);
        self::assertSame('Event 1', $payload['member'][0]['eventDetails']['name']);
        self::assertSame('Open 17.5', $payload['member'][0]['workoutDetails']['name']);
        self::assertArrayHasKey('scoreDetails', $payload['member'][0]);
        self::assertSame([
            'rank' => '8',
            'division' => 'Women',
            'divisionSourceId' => 'women',
            'format' => 'Individual',
            'formatSlug' => 'individual',
        ], $payload['member'][0]['participationDetails']);
        self::assertContains('/api/athletes/'.$cornerProfile->getId(), array_column($payload['member'], 'athlete'));
        self::assertNotContains('/api/athletes/'.$otherAthlete->getId(), array_column($payload['member'], 'athlete'));
    }

    public function testAthleteResultSummaryHidesPlaceholderWorkoutFlow(): void
    {
        $entityManager = $this->getEntityManager();
        $workout = new Workout(
            'Workout 1',
            '*',
            null,
            null,
            null,
            new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::OTHER), 2025),
        );
        $athlete = new Athlete('Bruno Guignard', 'competition_corner', 'bruno-corner');
        $competition = (new Competition('Marseille Throwdown 2025', 'competition_corner', '15984'))
            ->setSeason(2025);
        $event = (new CompetitionEvent($competition, 'Workout 1', 'competition_corner', '15984-workout-1'))
            ->setWorkout($workout);
        $result = new WorkoutResult($athlete, $event, new Score(ScoreTypeEnum::TIME, '10:00'), 'competition_corner', 'bruno-marseille-1');

        foreach ([$workout, $athlete, $competition, $event, $result] as $entity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();
        $entityManager->clear();

        $this->browser()->request('GET', sprintf('/api/athletes/%s/result-summary', $athlete->getId()));

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('Workout 1', $payload['member'][0]['workoutDetails']['name']);
        self::assertNull($payload['member'][0]['workoutDetails']['flow']);
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

    public function testWorkoutGenerationIsNotPubliclyListedOrTriggeredByLegacyRoutes(): void
    {
        $this->browser()->request('GET', '/api/workout_generations');

        self::assertResponseStatusCodeSame(404);

        $this->browser()->request('GET', '/api/workout-generator/00000000-0000-0000-0000-000000000000');

        self::assertResponseStatusCodeSame(404);

        $this->browser()->request('POST', '/api/workout-generator/00000000-0000-0000-0000-000000000000');

        self::assertResponseStatusCodeSame(404);

        $this->browser()->request('POST', '/api/simple-workout-generator/00000000-0000-0000-0000-000000000000');

        self::assertResponseStatusCodeSame(404);
    }

    public function testFrontendCanCreateAndUpdateWorkoutGenerationDraft(): void
    {
        $this->browser()->request('GET', '/api/workout-generation-flow/options');

        self::assertResponseIsSuccessful();

        $options = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertContains('AMRAP', array_column($options['workoutTypes'], 'name'));
        self::assertContains('barbell', array_column($options['implements'], 'name'));

        $this->browser()->request(
            'POST',
            '/api/workout-generation-flow',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'WOD API Draft',
                'timeCap' => 15,
                'movementGenerationType' => 'selected movements',
                'workoutType' => 'AMRAP',
                'numberOfRounds' => 1,
                'movementTypes' => ['Weightlifting'],
                'isTeamWorkout' => false,
                'movementDifficulty' => 'Intermediate',
                'mandatoryBodyParts' => [],
                'availableImplements' => ['barbell'],
                'numberOfDifferentMovements' => 2,
                'bannedMovements' => [],
                'mandatoryMovements' => [],
                'intervalsTime' => null,
                'intervalsRestTime' => null,
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(201);

        $draft = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('WOD API Draft', $draft['name']);
        self::assertSame('AMRAP', $draft['workoutType']['name']);

        $this->browser()->request('GET', sprintf('/api/workout-generation-flow/%s/possible-movements', $draft['id']));

        self::assertResponseIsSuccessful();

        $possibleMovements = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $movementNames = array_column($possibleMovements['movements'], 'name');
        self::assertContains('Deadlift', $movementNames);

        $this->browser()->request(
            'PATCH',
            sprintf('/api/workout-generation-flow/%s', $draft['id']),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'timeCap' => 18,
                'mandatoryMovements' => [$possibleMovements['movements'][0]['id']],
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseIsSuccessful();

        $updatedDraft = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(18, $updatedDraft['timeCap']);
        self::assertCount(1, $updatedDraft['mandatoryMovements']);

        self::assertNotNull($this->getRepository(WorkoutGeneration::class)->find($draft['id']));
    }

    public function testWorkoutGenerationDraftRejectsInvalidJsonPayload(): void
    {
        $this->browser()->request(
            'POST',
            '/api/workout-generation-flow',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"name": "Broken draft",'
        );

        self::assertResponseStatusCodeSame(400);
        self::assertSame(0, $this->getRepository(WorkoutGeneration::class)->count([]));
    }

    public function testWorkoutGenerationDraftRejectsNonObjectJsonPayload(): void
    {
        $entityManager = $this->getEntityManager();
        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Existing draft')
            ->setTimeCap(15)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::AMRAP))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty(new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE))
            ->setNumberOfDifferentMovements(1)
            ->setNumberOfRounds(1)
            ->setIsTeamWorkout(false);

        $entityManager->persist($workoutGeneration);
        $entityManager->flush();

        $this->browser()->request(
            'PATCH',
            sprintf('/api/workout-generation-flow/%s', $workoutGeneration->getId()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '"noop"'
        );

        self::assertResponseStatusCodeSame(400);
        self::assertSame('Existing draft', $this->getRepository(WorkoutGeneration::class)->find($workoutGeneration->getId())?->getName());
    }

    public function testWorkoutGenerationDraftRejectsBlankName(): void
    {
        $this->browser()->request(
            'POST',
            '/api/workout-generation-flow',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => '   ',
                'timeCap' => 15,
                'movementGenerationType' => 'selected movements',
                'workoutType' => 'AMRAP',
                'numberOfRounds' => 1,
                'movementTypes' => [],
                'isTeamWorkout' => false,
                'movementDifficulty' => 'Intermediate',
                'mandatoryBodyParts' => [],
                'availableImplements' => [],
                'numberOfDifferentMovements' => 1,
                'bannedMovements' => [],
                'mandatoryMovements' => [],
                'intervalsTime' => null,
                'intervalsRestTime' => null,
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->getRepository(WorkoutGeneration::class)->count([]));
    }

    public function testWorkoutGenerationDraftRejectsMissingRequiredFields(): void
    {
        $this->browser()->request(
            'POST',
            '/api/workout-generation-flow',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Missing required field draft',
                'movementGenerationType' => 'selected movements',
                'workoutType' => 'AMRAP',
                'numberOfRounds' => 1,
                'movementTypes' => [],
                'isTeamWorkout' => false,
                'movementDifficulty' => 'Intermediate',
                'mandatoryBodyParts' => [],
                'availableImplements' => [],
                'numberOfDifferentMovements' => 1,
                'bannedMovements' => [],
                'mandatoryMovements' => [],
                'intervalsTime' => null,
                'intervalsRestTime' => null,
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->getRepository(WorkoutGeneration::class)->count([]));
    }

    public function testWorkoutGenerationDraftRejectsInvalidRequiredCatalogReference(): void
    {
        $this->browser()->request(
            'POST',
            '/api/workout-generation-flow',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Invalid catalog draft',
                'timeCap' => 15,
                'movementGenerationType' => 'selected movements',
                'workoutType' => 'Not a workout type',
                'numberOfRounds' => 1,
                'movementTypes' => [],
                'isTeamWorkout' => false,
                'movementDifficulty' => 'Intermediate',
                'mandatoryBodyParts' => [],
                'availableImplements' => [],
                'numberOfDifferentMovements' => 1,
                'bannedMovements' => [],
                'mandatoryMovements' => [],
                'intervalsTime' => null,
                'intervalsRestTime' => null,
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->getRepository(WorkoutGeneration::class)->count([]));
    }

    public function testWorkoutGenerationDraftRejectsInvalidNumericFields(): void
    {
        $this->browser()->request(
            'POST',
            '/api/workout-generation-flow',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Invalid numeric draft',
                'timeCap' => 'abc',
                'movementGenerationType' => 'selected movements',
                'workoutType' => 'AMRAP',
                'numberOfRounds' => 1,
                'movementTypes' => [],
                'isTeamWorkout' => false,
                'movementDifficulty' => 'Intermediate',
                'mandatoryBodyParts' => [],
                'availableImplements' => [],
                'numberOfDifferentMovements' => 1,
                'bannedMovements' => [],
                'mandatoryMovements' => [],
                'intervalsTime' => null,
                'intervalsRestTime' => null,
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->getRepository(WorkoutGeneration::class)->count([]));
    }

    public function testWorkoutGenerationDraftRejectsInvalidBooleanFields(): void
    {
        $this->browser()->request(
            'POST',
            '/api/workout-generation-flow',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Invalid boolean draft',
                'timeCap' => 15,
                'movementGenerationType' => 'selected movements',
                'workoutType' => 'AMRAP',
                'numberOfRounds' => 1,
                'movementTypes' => [],
                'isTeamWorkout' => 'false',
                'movementDifficulty' => 'Intermediate',
                'mandatoryBodyParts' => [],
                'availableImplements' => [],
                'numberOfDifferentMovements' => 1,
                'bannedMovements' => [],
                'mandatoryMovements' => [],
                'intervalsTime' => null,
                'intervalsRestTime' => null,
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->getRepository(WorkoutGeneration::class)->count([]));
    }

    public function testWorkoutGenerationDraftRejectsInvalidNullableTextFields(): void
    {
        $this->browser()->request(
            'POST',
            '/api/workout-generation-flow',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Invalid nullable text draft',
                'stimulus' => ['Engine long'],
                'timeCap' => 15,
                'movementGenerationType' => 'selected movements',
                'workoutType' => 'AMRAP',
                'numberOfRounds' => 1,
                'movementTypes' => [],
                'isTeamWorkout' => false,
                'movementDifficulty' => 'Intermediate',
                'mandatoryBodyParts' => [],
                'availableImplements' => [],
                'numberOfDifferentMovements' => 1,
                'bannedMovements' => [],
                'mandatoryMovements' => [],
                'intervalsTime' => null,
                'intervalsRestTime' => null,
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->getRepository(WorkoutGeneration::class)->count([]));
    }

    public function testWorkoutGenerationDraftRejectsInvalidMovementReference(): void
    {
        $this->browser()->request(
            'POST',
            '/api/workout-generation-flow',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Invalid movement reference draft',
                'timeCap' => 15,
                'movementGenerationType' => 'selected movements',
                'workoutType' => 'AMRAP',
                'numberOfRounds' => 1,
                'movementTypes' => [],
                'isTeamWorkout' => false,
                'movementDifficulty' => 'Intermediate',
                'mandatoryBodyParts' => [],
                'availableImplements' => [],
                'numberOfDifferentMovements' => 1,
                'bannedMovements' => [],
                'mandatoryMovements' => ['not-a-uuid'],
                'intervalsTime' => null,
                'intervalsRestTime' => null,
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->getRepository(WorkoutGeneration::class)->count([]));
    }

    public function testWorkoutGenerationDraftRejectsMovementThatIsBothMandatoryAndBanned(): void
    {
        $row = $this->getReference(MovementData::MOVEMENT_ROW, Movement::class);
        $rowId = $row->getId()->toString();

        $this->browser()->request(
            'POST',
            '/api/workout-generation-flow',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Conflicting movement filters draft',
                'timeCap' => 15,
                'movementGenerationType' => 'selected movements',
                'workoutType' => 'AMRAP',
                'numberOfRounds' => 1,
                'movementTypes' => [],
                'isTeamWorkout' => false,
                'movementDifficulty' => 'Intermediate',
                'mandatoryBodyParts' => [],
                'availableImplements' => [],
                'numberOfDifferentMovements' => 1,
                'bannedMovements' => [$rowId],
                'mandatoryMovements' => [$rowId],
                'intervalsTime' => null,
                'intervalsRestTime' => null,
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->getRepository(WorkoutGeneration::class)->count([]));
    }

    public function testWorkoutGenerationDraftRejectsTooManyMandatoryMovements(): void
    {
        $row = $this->getReference(MovementData::MOVEMENT_ROW, Movement::class);
        $run = $this->getReference(MovementData::MOVEMENT_RUN, Movement::class);

        $this->browser()->request(
            'POST',
            '/api/workout-generation-flow',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Too many mandatory movements draft',
                'timeCap' => 15,
                'movementGenerationType' => 'selected movements',
                'workoutType' => 'AMRAP',
                'numberOfRounds' => 1,
                'movementTypes' => [],
                'isTeamWorkout' => false,
                'movementDifficulty' => 'Intermediate',
                'mandatoryBodyParts' => [],
                'availableImplements' => [],
                'numberOfDifferentMovements' => 1,
                'bannedMovements' => [],
                'mandatoryMovements' => [
                    $row->getId()->toString(),
                    $run->getId()->toString(),
                ],
                'intervalsTime' => null,
                'intervalsRestTime' => null,
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->getRepository(WorkoutGeneration::class)->count([]));
    }

    public function testWorkoutGenerationDraftRejectsDuplicateMandatoryMovementReferences(): void
    {
        $row = $this->getReference(MovementData::MOVEMENT_ROW, Movement::class);
        $rowId = $row->getId()->toString();

        $this->browser()->request(
            'POST',
            '/api/workout-generation-flow',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Duplicate mandatory movement draft',
                'timeCap' => 15,
                'movementGenerationType' => 'selected movements',
                'workoutType' => 'AMRAP',
                'numberOfRounds' => 1,
                'movementTypes' => [],
                'isTeamWorkout' => false,
                'movementDifficulty' => 'Intermediate',
                'mandatoryBodyParts' => [],
                'availableImplements' => [],
                'numberOfDifferentMovements' => 1,
                'bannedMovements' => [],
                'mandatoryMovements' => [$rowId, $rowId],
                'intervalsTime' => null,
                'intervalsRestTime' => null,
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->getRepository(WorkoutGeneration::class)->count([]));
    }

    public function testWorkoutGenerationDraftRejectsInvalidCatalogListReference(): void
    {
        $this->browser()->request(
            'POST',
            '/api/workout-generation-flow',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Invalid catalog list draft',
                'timeCap' => 15,
                'movementGenerationType' => 'selected movements',
                'workoutType' => 'AMRAP',
                'numberOfRounds' => 1,
                'movementTypes' => ['Not a movement type'],
                'isTeamWorkout' => false,
                'movementDifficulty' => 'Intermediate',
                'mandatoryBodyParts' => [],
                'availableImplements' => [],
                'numberOfDifferentMovements' => 1,
                'bannedMovements' => [],
                'mandatoryMovements' => [],
                'intervalsTime' => null,
                'intervalsRestTime' => null,
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->getRepository(WorkoutGeneration::class)->count([]));
    }

    public function testWorkoutGenerationDraftRejectsDuplicateCatalogListReferences(): void
    {
        $this->browser()->request(
            'POST',
            '/api/workout-generation-flow',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Duplicate catalog reference draft',
                'timeCap' => 15,
                'movementGenerationType' => 'selected movements',
                'workoutType' => 'AMRAP',
                'numberOfRounds' => 1,
                'movementTypes' => [],
                'isTeamWorkout' => false,
                'movementDifficulty' => 'Intermediate',
                'mandatoryBodyParts' => [],
                'availableImplements' => ['barbell', 'barbell'],
                'numberOfDifferentMovements' => 1,
                'bannedMovements' => [],
                'mandatoryMovements' => [],
                'intervalsTime' => null,
                'intervalsRestTime' => null,
            ], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->getRepository(WorkoutGeneration::class)->count([]));
    }

    public function testFrontendCanRegenerateWorkoutForTheSameDraft(): void
    {
        $this->browser()->disableReboot();
        static::getContainer()->set(WorkoutCreatorServiceInterface::class, new class implements WorkoutCreatorServiceInterface {
            public function createWorkout(WorkoutGeneration $workoutGeneration): Workout
            {
                return (new Workout(
                    $workoutGeneration->getName(),
                    'Generated flow',
                    $workoutGeneration->getNumberOfRounds(),
                    $workoutGeneration->getTimeCap(),
                    $workoutGeneration->getWorkoutType(),
                    new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), 2026),
                    $workoutGeneration->getAvailableImplements()->toArray(),
                    $workoutGeneration->getMandatoryMovements()->toArray(),
                ))
                    ->setWorkoutGeneration($workoutGeneration)
                    ->setGenerationPrompt('Prompt sent to OpenAI');
            }

            public function createWorkoutVariants(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        });

        $this->browser()->request(
            'POST',
            '/api/workout-generation-flow',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Regenerated WOD',
                'stimulus' => 'Engine long',
                'stimulusIntent' => 'Volume soutenu, respiration stable, gestion du pacing.',
                'timeCap' => 15,
                'movementGenerationType' => 'selected movements',
                'workoutType' => 'AMRAP',
                'numberOfRounds' => 1,
                'movementTypes' => ['Weightlifting'],
                'isTeamWorkout' => false,
                'movementDifficulty' => 'Intermediate',
                'mandatoryBodyParts' => [],
                'availableImplements' => ['barbell'],
                'numberOfDifferentMovements' => 1,
                'bannedMovements' => [],
                'mandatoryMovements' => [],
                'intervalsTime' => null,
                'intervalsRestTime' => null,
            ], JSON_THROW_ON_ERROR)
        );
        self::assertResponseStatusCodeSame(201);
        $draft = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->browser()->request('POST', sprintf('/api/workout-generation-flow/%s/workout', $draft['id']));
        self::assertResponseStatusCodeSame(201);
        $firstWorkout = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->browser()->request('POST', sprintf('/api/workout-generation-flow/%s/workout', $draft['id']));
        self::assertResponseStatusCodeSame(201);
        $secondWorkout = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame($firstWorkout['id'], $secondWorkout['id']);
        self::assertSame('Regenerated WOD', $secondWorkout['name']);
        self::assertArrayNotHasKey('generationPrompt', $secondWorkout);
        $workoutGeneration = $this->getRepository(WorkoutGeneration::class)->find($draft['id']);
        self::assertSame('Engine long', $workoutGeneration->getStimulus());
        self::assertSame('Volume soutenu, respiration stable, gestion du pacing.', $workoutGeneration->getStimulusIntent());
        self::assertSame(1, $this->getRepository(Workout::class)->count(['workoutGeneration' => $workoutGeneration]));

        $admin = (new User('workout-admin@example.com'))
            ->setPassword('test-password')
            ->setRoles(['ROLE_ADMIN']);
        $this->getEntityManager()->persist($admin);
        $this->getEntityManager()->flush();

        $this->browser()->loginUser($admin);
        $this->browser()->request('POST', sprintf('/api/workout-generation-flow/%s/workout', $draft['id']));
        self::assertResponseStatusCodeSame(201);
        $adminWorkout = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame($firstWorkout['id'], $adminWorkout['id']);
        self::assertSame('Prompt sent to OpenAI', $adminWorkout['generationPrompt']);
    }

    public function testFrontendCanRequestWorkoutVariantsForADraft(): void
    {
        $this->browser()->disableReboot();
        static::getContainer()->set(WorkoutCreatorServiceInterface::class, new class implements WorkoutCreatorServiceInterface {
            public function createWorkout(WorkoutGeneration $workoutGeneration): Workout
            {
                throw new \RuntimeException('Unexpected final generation call.');
            }

            public function createWorkoutVariants(WorkoutGeneration $workoutGeneration): array
            {
                return [
                    [
                        'title' => 'Engine progressif',
                        'intent' => 'Installer un rythme respiratoire stable avant d’accélérer.',
                        'format' => 'AMRAP 16',
                        'movementNames' => ['Row'],
                        'summary' => 'Une option simple pour prioriser le pacing.',
                    ],
                    [
                        'title' => 'Sprint contrôlé',
                        'intent' => 'Limiter les transitions et garder des séries courtes.',
                        'format' => 'For time',
                        'movementNames' => ['Row'],
                        'summary' => 'Une option plus nerveuse mais encore répétable.',
                    ],
                ];
            }
        });

        $this->browser()->request(
            'POST',
            '/api/workout-generation-flow',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Variant WOD',
                'stimulus' => 'Engine long',
                'stimulusIntent' => 'Volume soutenu, respiration stable, gestion du pacing.',
                'timeCap' => 16,
                'movementGenerationType' => 'selected movements',
                'workoutType' => 'AMRAP',
                'numberOfRounds' => 1,
                'movementTypes' => ['Weightlifting'],
                'isTeamWorkout' => false,
                'movementDifficulty' => 'Intermediate',
                'mandatoryBodyParts' => [],
                'availableImplements' => ['barbell'],
                'numberOfDifferentMovements' => 1,
                'bannedMovements' => [],
                'mandatoryMovements' => [],
                'intervalsTime' => null,
                'intervalsRestTime' => null,
            ], JSON_THROW_ON_ERROR)
        );
        self::assertResponseStatusCodeSame(201);
        $draft = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->browser()->request('POST', sprintf('/api/workout-generation-flow/%s/variants', $draft['id']));
        self::assertResponseIsSuccessful();
        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame($draft['id'], $payload['workoutGenerationId']);
        self::assertCount(2, $payload['variants']);
        self::assertSame('Engine progressif', $payload['variants'][0]['title']);
        self::assertSame(['Row'], $payload['variants'][0]['movementNames']);
    }

    public function testWorkoutGenerationReturnsBadGatewayWhenCreatorRejectsPayload(): void
    {
        $this->browser()->disableReboot();
        static::getContainer()->set(WorkoutCreatorServiceInterface::class, new class implements WorkoutCreatorServiceInterface {
            public function createWorkout(WorkoutGeneration $workoutGeneration): Workout
            {
                throw new \RuntimeException('OpenAI workout generation listed movement "Row" but did not include it in the workout flow.');
            }

            public function createWorkoutVariants(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }
        });

        $this->browser()->request(
            'POST',
            '/api/workout-generation-flow',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Rejected generated WOD',
                'timeCap' => 15,
                'movementGenerationType' => 'selected movements',
                'workoutType' => 'AMRAP',
                'numberOfRounds' => 1,
                'movementTypes' => ['Weightlifting'],
                'isTeamWorkout' => false,
                'movementDifficulty' => 'Intermediate',
                'mandatoryBodyParts' => [],
                'availableImplements' => ['barbell'],
                'numberOfDifferentMovements' => 1,
                'bannedMovements' => [],
                'mandatoryMovements' => [],
                'intervalsTime' => null,
                'intervalsRestTime' => null,
            ], JSON_THROW_ON_ERROR)
        );
        self::assertResponseStatusCodeSame(201);
        $draft = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->browser()->request('POST', sprintf('/api/workout-generation-flow/%s/workout', $draft['id']));

        self::assertResponseStatusCodeSame(502);
        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('OpenAI workout generation listed movement "Row" but did not include it in the workout flow.', $payload['error']);
        $workoutGeneration = $this->getRepository(WorkoutGeneration::class)->find($draft['id']);
        self::assertSame(0, $this->getRepository(Workout::class)->count(['workoutGeneration' => $workoutGeneration]));
    }

    public function testWorkoutGenerationMatchesCatalogFiltersByNameWhenCatalogRowsAreDuplicated(): void
    {
        $entityManager = $this->getEntityManager();
        $duplicateCardio = new MovementType(MovementTypeEnum::CARDIO);
        $duplicateWeightlifting = new MovementType(MovementTypeEnum::WEIGHTLIFTING);
        $duplicateGymnastic = new MovementType(MovementTypeEnum::GYMNASTIC);
        $duplicateElite = new MovementDifficulty(MovementDifficultyEnum::ELITE);
        $duplicateBarbell = new Implement(ImplementEnum::BARBELL, null);
        $duplicatePullUpBar = new Implement(ImplementEnum::PULL_UP_BAR, null);
        $duplicateLegs = new BodyPart(BodyPartEnum::LEGS);
        $duplicateUpperBack = new BodyPart(BodyPartEnum::UPPER_BACK);

        foreach ([
            $duplicateCardio,
            $duplicateWeightlifting,
            $duplicateGymnastic,
            $duplicateElite,
            $duplicateBarbell,
            $duplicatePullUpBar,
            $duplicateLegs,
            $duplicateUpperBack,
        ] as $entity) {
            $entityManager->persist($entity);
        }

        $workoutGeneration = (new WorkoutGeneration())
            ->setName('Duplicated catalog WOD')
            ->setTimeCap(16)
            ->setWorkoutType(new WorkoutType(WorkoutTypeEnum::FOR_TIME))
            ->setMovementGenerationType(new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT))
            ->setMovementDifficulty($duplicateElite)
            ->setMovementTypes([$duplicateCardio, $duplicateWeightlifting, $duplicateGymnastic])
            ->setAvailableImplements([$duplicateBarbell, $duplicatePullUpBar])
            ->setMandatoryBodyParts([$duplicateLegs, $duplicateUpperBack])
            ->setNumberOfDifferentMovements(3)
            ->setNumberOfRounds(3)
            ->setIsTeamWorkout(true);

        $entityManager->persist($workoutGeneration);
        $entityManager->flush();
        $entityManager->clear();

        $this->browser()->request('GET', sprintf('/api/workout-generation-flow/%s/possible-movements', $workoutGeneration->getId()));

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $movementNames = array_column($payload['movements'], 'name');

        self::assertContains('Deadlift', $movementNames);
        self::assertContains('Run', $movementNames);
    }
}
