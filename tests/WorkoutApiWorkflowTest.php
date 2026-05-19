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
            ->setFieldSize(40);
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
