<?php

namespace App\Tests;

use App\DataFixtures\MovementData;
use App\DataFixtures\WorkoutData;
use App\Entity\Competition\Athlete;
use App\Entity\Competition\AthletePublicAnalysis;
use App\Entity\Competition\Competition;
use App\Entity\Competition\CompetitionDivision;
use App\Entity\Competition\CompetitionEvent;
use App\Entity\Competition\CompetitionOfficialQualification;
use App\Entity\Competition\CompetitionParticipation;
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
use App\Entity\WorkoutGeneration\WorkoutAiGenerationUsage;
use App\Entity\WorkoutGeneration\WorkoutGeneration;
use App\Services\Workout\WorkoutCreatorServiceInterface;

/**
 * @group integration
 * @group slow
 */
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

    public function testFrontendCanFilterWorkoutCatalogByStructuredMovementAndImplement(): void
    {
        $this->browser()->request('GET', '/api/workout-catalog?movements.name=Double%20Under&implements.name=Jump%20Rope&timeCap=40&itemsPerPage=1000');

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $workouts = $payload['member'] ?? $payload['hydra:member'] ?? [];
        $names = array_map(static fn (array $workout): ?string => $workout['name'] ?? null, $workouts);

        self::assertContains('Open 17.5', $names);
        self::assertNotContains('Fran', $names);
    }

    public function testFrontendCanFilterGirlsAndHeroesCatalogByMonwodCatalogSource(): void
    {
        $this->browser()->request('GET', '/api/workout-catalog?name=fran&sourceName=monwod_catalog&itemsPerPage=1000');

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $workouts = $payload['member'] ?? $payload['hydra:member'] ?? [];
        $names = array_map(static fn (array $workout): ?string => $workout['name'] ?? null, $workouts);

        self::assertContains('Fran', $names);
    }

    public function testFrontendSourceFilterStillUsesWorkoutSourceNameForImportedSources(): void
    {
        $entityManager = $this->getEntityManager();
        $origin = new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::OTHER), null);
        $workoutType = new WorkoutType(WorkoutTypeEnum::FOR_TIME);
        $crossfitGamesWorkout = (new Workout(
            'Source filter classic test',
            "For time:\n10 Burpees",
            1,
            10,
            $workoutType,
            $origin,
        ))->setSourceName('crossfit_games');
        $competitionCornerWorkout = (new Workout(
            'Source filter classic test',
            "For time:\n10 Burpees",
            1,
            10,
            $workoutType,
            $origin,
        ))->setSourceName('competition_corner');
        $crossfitGamesPaginationWorkout = (new Workout(
            'A source pagination test',
            "For time:\n10 Thrusters",
            1,
            10,
            $workoutType,
            $origin,
        ))->setSourceName('crossfit_games');
        $competitionCornerPaginationWorkout = (new Workout(
            'B source pagination test',
            "For time:\n10 Pull-Ups",
            1,
            10,
            $workoutType,
            $origin,
        ))->setSourceName('competition_corner');

        foreach ([$origin, $workoutType, $crossfitGamesWorkout, $competitionCornerWorkout, $crossfitGamesPaginationWorkout, $competitionCornerPaginationWorkout] as $entity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();

        $this->browser()->request('GET', '/api/workout-catalog?name=source%20filter%20classic&sourceName=crossfit_games&itemsPerPage=1000');

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $workouts = $payload['member'] ?? $payload['hydra:member'] ?? [];

        self::assertSame(1, $payload['totalItems']);
        self::assertSame('Source filter classic test', $workouts[0]['name'] ?? null);
        self::assertSame('crossfit_games', $workouts[0]['sourceName'] ?? null);

        $this->browser()->request('GET', '/api/workout-catalog?name=source%20filter%20classic&sourceNames=crossfit_games&sourceNames=competition_corner&itemsPerPage=1000');

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $workouts = $payload['member'] ?? $payload['hydra:member'] ?? [];

        self::assertSame(1, $payload['totalItems']);
        self::assertSame(['competition_corner', 'crossfit_games'], $workouts[0]['sources'] ?? null);

        $this->browser()->request('GET', '/api/workout-catalog?name=source%20pagination%20test&sourceNames=crossfit_games&sourceNames=competition_corner&itemsPerPage=1');

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $workouts = $payload['member'] ?? $payload['hydra:member'] ?? [];
        $next = $payload['view']['next'] ?? null;

        self::assertSame(2, $payload['totalItems']);
        self::assertCount(1, $workouts);
        self::assertSame('A source pagination test', $workouts[0]['name'] ?? null);
        self::assertIsString($next);
        self::assertStringContainsString('sourceNames%5B', $next);
        self::assertStringContainsString('=crossfit_games', $next);
        self::assertStringContainsString('=competition_corner', $next);

        $this->browser()->request('GET', $next);

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $workouts = $payload['member'] ?? $payload['hydra:member'] ?? [];

        self::assertSame(2, $payload['totalItems']);
        self::assertCount(1, $workouts);
        self::assertSame('B source pagination test', $workouts[0]['name'] ?? null);
    }

    public function testWorkoutCatalogCanFilterCompetitionSourceByMovementInImportedFlow(): void
    {
        $entityManager = $this->getEntityManager();
        $origin = new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::OTHER), 2026);
        $workoutType = new WorkoutType(WorkoutTypeEnum::FOR_TIME);
        $competition = (new Competition('Thruster Competition', 'competition_corner', 'thruster-competition'))
            ->setSeason(2026);
        $publicWorkout = (new Workout(
            'Competition thruster filter test',
            "For time:\n21 Thrusters (95/65 lb)\n21 Pull-Ups",
            1,
            10,
            $workoutType,
            $origin,
        ))
            ->setSourceName('competition_corner')
            ->setExternalId('competition-thruster-filter-public');
        $publicEvent = (new CompetitionEvent($competition, 'Workout 1', 'competition_corner', 'thruster-competition-workout-1'))
            ->setWorkout($publicWorkout);
        $auditWorkout = (new Workout(
            'Audit competition thruster filter test',
            "For time:\n21 Thrusters",
            1,
            10,
            $workoutType,
            $origin,
        ))
            ->setSourceName('monwod_audit')
            ->setExternalId('competition-thruster-filter-audit');
        $auditEvent = (new CompetitionEvent($competition, 'Audit Workout', 'monwod_audit', 'thruster-competition-audit-workout'))
            ->setWorkout($auditWorkout);
        $hangPowerCleanWorkout = (new Workout(
            'Competition hang power clean filter ambiguity test',
            "For time:\n21 Hang Power Cleans",
            1,
            10,
            $workoutType,
            $origin,
        ))
            ->setSourceName('competition_corner')
            ->setExternalId('competition-hang-power-clean-filter');
        $hangPowerCleanEvent = (new CompetitionEvent($competition, 'Workout 2', 'competition_corner', 'thruster-competition-workout-2'))
            ->setWorkout($hangPowerCleanWorkout);
        $boxJumpWorkout = (new Workout(
            'Competition box jump filter test',
            "For time:\n21 Box Jumps\n21 Sit-Ups",
            1,
            10,
            $workoutType,
            $origin,
        ))
            ->setSourceName('competition_corner')
            ->setExternalId('competition-box-jump-filter');
        $boxJumpEvent = (new CompetitionEvent($competition, 'Workout 3', 'competition_corner', 'thruster-competition-workout-3'))
            ->setWorkout($boxJumpWorkout);
        $boxJumpOverWorkout = (new Workout(
            'Competition box jump over filter ambiguity test',
            "For time:\n21 Box Jumps Over\n21 Sit-Ups",
            1,
            10,
            $workoutType,
            $origin,
        ))
            ->setSourceName('competition_corner')
            ->setExternalId('competition-box-jump-over-filter');
        $boxJumpOverEvent = (new CompetitionEvent($competition, 'Workout 4', 'competition_corner', 'thruster-competition-workout-4'))
            ->setWorkout($boxJumpOverWorkout);

        foreach ([$origin, $workoutType, $competition, $publicWorkout, $auditWorkout, $hangPowerCleanWorkout, $boxJumpWorkout, $boxJumpOverWorkout, $publicEvent, $auditEvent, $hangPowerCleanEvent, $boxJumpEvent, $boxJumpOverEvent] as $entity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();
        $entityManager->clear();

        $this->browser()->request('GET', '/api/workout-catalog?source=competition&movement=thruster&itemsPerPage=1000');

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $workouts = $payload['member'] ?? $payload['hydra:member'] ?? [];
        $names = array_map(static fn (array $workout): ?string => $workout['name'] ?? null, $workouts);

        self::assertContains('Competition thruster filter test', $names);
        self::assertNotContains('Audit competition thruster filter test', $names);

        $this->browser()->request('GET', '/api/workout-catalog?source=competition&movement=clean&itemsPerPage=1000');

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $workouts = $payload['member'] ?? $payload['hydra:member'] ?? [];
        $names = array_map(static fn (array $workout): ?string => $workout['name'] ?? null, $workouts);

        self::assertNotContains('Competition hang power clean filter ambiguity test', $names);

        $this->browser()->request('GET', '/api/workout-catalog?source=competition&movement=box%20jump&itemsPerPage=1000');

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $workouts = $payload['member'] ?? $payload['hydra:member'] ?? [];
        $names = array_map(static fn (array $workout): ?string => $workout['name'] ?? null, $workouts);

        self::assertContains('Competition box jump filter test', $names);
        self::assertNotContains('Competition box jump over filter ambiguity test', $names);
    }

    public function testWorkoutCatalogCanFilterCrossfitGamesSourceByDoubleUnderAliasInImportedFlow(): void
    {
        $entityManager = $this->getEntityManager();
        $origin = new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::OTHER), 2026);
        $workoutType = new WorkoutType(WorkoutTypeEnum::AMRAP);
        $doubleUnderWorkout = (new Workout(
            '11.1 alias filter test',
            "AMRAP 10 minutes\n30 Double-unders\n15 Power Snatches",
            1,
            10,
            $workoutType,
            $origin,
        ))
            ->setSourceName('crossfit_games')
            ->setExternalId('open-11-1-alias-filter');
        $unrelatedWorkout = (new Workout(
            'CrossFit Games unrelated double filter test',
            "AMRAP 10 minutes\n30 Dumbbell Snatches\n15 Burpees",
            1,
            10,
            $workoutType,
            $origin,
        ))
            ->setSourceName('crossfit_games')
            ->setExternalId('open-unrelated-double-filter');

        foreach ([$origin, $workoutType, $doubleUnderWorkout, $unrelatedWorkout] as $entity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();
        $entityManager->clear();

        $this->browser()->request('GET', '/api/workout-catalog?sourceName=crossfit_games&movement=Double%20Under&itemsPerPage=1000');

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $workouts = $payload['member'] ?? $payload['hydra:member'] ?? [];
        $names = array_map(static fn (array $workout): ?string => $workout['name'] ?? null, $workouts);

        self::assertContains('11.1 alias filter test', $names);
        self::assertNotContains('CrossFit Games unrelated double filter test', $names);
    }

    public function testWorkoutCatalogDeduplicatesExactCanonicalDuplicatesByDefault(): void
    {
        $entityManager = $this->getEntityManager();
        $origin = new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::OTHER), 2026);
        $workoutType = new WorkoutType(WorkoutTypeEnum::FOR_TIME);
        $first = (new Workout(
            'Canonical duplicate API test',
            "For time:\n21-15-9\nThrusters (95/65 lb)\nPull-Ups",
            1,
            10,
            $workoutType,
            $origin,
        ))
            ->setSourceName('crossfit_games')
            ->setExternalId('canonical-duplicate-games')
            ->setSourceUrl('https://example.test/games')
            ->setCanonicalFingerprint('canonical-duplicate-api-fingerprint');
        $second = (new Workout(
            'Canonical duplicate API-test',
            "For time:\n\n21 15 9\nThrusters 95/65 lb\nPull Ups",
            1,
            10,
            $workoutType,
            $origin,
        ))
            ->setSourceName('competition_corner')
            ->setExternalId('canonical-duplicate-corner')
            ->setSourceUrl('https://example.test/corner')
            ->setCanonicalFingerprint('canonical-duplicate-api-fingerprint');
        $third = (new Workout(
            ' Canonical duplicate API test ',
            "For time:\n21-15-9\nThrusters (95/65 lb)\nPull-Ups",
            1,
            10,
            $workoutType,
            $origin,
        ))
            ->setSourceName('crossfit_games')
            ->setExternalId('canonical-duplicate-games-men')
            ->setSourceUrl('https://example.test/games-men')
            ->setCanonicalFingerprint('canonical-duplicate-api-fingerprint');
        $athlete = new Athlete('Canonical Athlete', 'crossfit_games', 'canonical-athlete');
        $gamesCompetition = (new Competition('Canonical Games', 'crossfit_games', 'canonical-games'))
            ->setSeason(2026);
        $cornerCompetition = (new Competition('Canonical Throwdown', 'competition_corner', 'canonical-throwdown'))
            ->setSeason(2025);
        $gamesEvent = (new CompetitionEvent($gamesCompetition, 'Final Fran', 'crossfit_games', 'canonical-games-final'))
            ->setEventOrder(1)
            ->setWorkout($first);
        $gamesMenEvent = (new CompetitionEvent($gamesCompetition, 'Final Fran', 'crossfit_games', 'canonical-games-final-men'))
            ->setEventOrder(1)
            ->setWorkout($third);
        $cornerEvent = (new CompetitionEvent($cornerCompetition, 'Qualifier Fran', 'competition_corner', 'canonical-corner-qualifier'))
            ->setEventOrder(2)
            ->setWorkout($second);
        $gamesDivision = new CompetitionDivision($gamesCompetition, 'Elite Women', 'crossfit_games', 'canonical-games-elite-women');
        $gamesMenDivision = new CompetitionDivision($gamesCompetition, 'Elite Men', 'crossfit_games', 'canonical-games-elite-men');
        $cornerDivision = new CompetitionDivision($cornerCompetition, 'RX Men', 'competition_corner', 'canonical-corner-rx-men');
        $gamesResult = (new WorkoutResult($athlete, $gamesEvent, new Score(ScoreTypeEnum::TIME, '2:59'), 'crossfit_games', 'canonical-games-result'))
            ->setCompetitionDivision($gamesDivision)
            ->setDivision('Elite Women');
        $gamesMenResult = (new WorkoutResult($athlete, $gamesMenEvent, new Score(ScoreTypeEnum::TIME, '2:55'), 'crossfit_games', 'canonical-games-men-result'))
            ->setCompetitionDivision($gamesMenDivision)
            ->setDivision('Elite Men');
        $cornerResult = (new WorkoutResult($athlete, $cornerEvent, new Score(ScoreTypeEnum::TIME, '3:10'), 'competition_corner', 'canonical-corner-result'))
            ->setCompetitionDivision($cornerDivision)
            ->setDivision('RX Men');

        foreach ([$origin, $workoutType, $first, $second, $third, $athlete, $gamesCompetition, $cornerCompetition, $gamesEvent, $gamesMenEvent, $cornerEvent, $gamesDivision, $gamesMenDivision, $cornerDivision, $gamesResult, $gamesMenResult, $cornerResult] as $entity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();
        $entityManager->clear();

        $this->browser()->request('GET', '/api/workout-catalog?q=pull%20ups&itemsPerPage=1000');

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $workouts = $payload['member'] ?? $payload['hydra:member'] ?? [];

        self::assertSame(1, $payload['totalItems']);
        self::assertCount(1, $workouts);
        self::assertStringStartsWith('Canonical duplicate API', trim((string) ($workouts[0]['name'] ?? '')));
        self::assertStringContainsString('Pull Ups', $workouts[0]['flow'] ?? '');
        self::assertSame(['flow'], $workouts[0]['matchDetails']['query']['fields'] ?? null);
        self::assertSame('canonical-duplicate-api-fingerprint', $workouts[0]['canonicalFingerprint'] ?? null);
        self::assertSame(3, $workouts[0]['occurrenceCount'] ?? null);
        self::assertCount(3, $workouts[0]['workoutIds'] ?? []);
        self::assertSame(['competition_corner', 'crossfit_games'], $workouts[0]['sources'] ?? null);
        self::assertCount(3, $workouts[0]['sourceReferences'] ?? []);
        self::assertCount(2, $workouts[0]['competitionContexts'] ?? []);
        self::assertSame(['Canonical Games', 'Canonical Throwdown'], array_column($workouts[0]['competitionContexts'], 'competitionName'));
        self::assertContainsOnly('array', array_column($workouts[0]['competitionContexts'], 'divisions'));
    }

    public function testWorkoutCatalogKeepsSameNameDifferentContentAsSeparateCanonicalWorkouts(): void
    {
        $entityManager = $this->getEntityManager();
        $origin = new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::OTHER), 2026);
        $workoutType = new WorkoutType(WorkoutTypeEnum::FOR_TIME);
        $cleanWorkout = (new Workout(
            'Canonical variant API test',
            "For time:\n30 Cleans",
            1,
            12,
            $workoutType,
            $origin,
        ))->setSourceName('crossfit_games');
        $snatchWorkout = (new Workout(
            'Canonical variant API test',
            "For time:\n30 Snatches",
            1,
            12,
            $workoutType,
            $origin,
        ))->setSourceName('competition_corner');

        foreach ([$origin, $workoutType, $cleanWorkout, $snatchWorkout] as $entity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();

        $this->browser()->request('GET', '/api/workout-catalog?name=canonical%20variant%20api%20test&itemsPerPage=1000');

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $workouts = $payload['member'] ?? $payload['hydra:member'] ?? [];
        $flows = array_map(static fn (array $workout): ?string => $workout['flow'] ?? null, $workouts);

        self::assertSame(2, $payload['totalItems']);
        self::assertContains("For time:\n30 Cleans", $flows);
        self::assertContains("For time:\n30 Snatches", $flows);
    }

    public function testWorkoutCatalogCanIncludeRawDuplicatesForAudit(): void
    {
        $entityManager = $this->getEntityManager();
        $origin = new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::OTHER), 2026);
        $workoutType = new WorkoutType(WorkoutTypeEnum::FOR_TIME);
        $first = (new Workout(
            'Canonical raw duplicate API test',
            "For time:\n10 Burpees",
            1,
            8,
            $workoutType,
            $origin,
        ))->setSourceName('crossfit_games');
        $second = (new Workout(
            'Canonical raw duplicate API test',
            "For time:\n10 Burpees",
            1,
            8,
            $workoutType,
            $origin,
        ))->setSourceName('competition_corner');

        foreach ([$origin, $workoutType, $first, $second] as $entity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();

        $this->browser()->request('GET', '/api/workout-catalog?name=canonical%20raw%20duplicate%20api%20test&includeDuplicates=true&itemsPerPage=1000');

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $workouts = $payload['member'] ?? $payload['hydra:member'] ?? [];
        $sources = array_map(static fn (array $workout): ?string => $workout['sourceName'] ?? null, $workouts);

        self::assertSame(2, $payload['totalItems']);
        self::assertCount(2, $workouts);
        self::assertContains('competition_corner', $sources);
        self::assertContains('crossfit_games', $sources);
        self::assertArrayNotHasKey('occurrenceCount', $workouts[0]);
    }

    public function testPublicWorkoutCatalogHidesInternalAuditWorkouts(): void
    {
        $entityManager = $this->getEntityManager();
        $origin = new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::OTHER), 2026);
        $workoutType = new WorkoutType(WorkoutTypeEnum::FOR_TIME);
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::RX);
        $movementGenerationType = new WorkoutMovementGenerationType(WorkoutMovementGenerationTypeEnum::MOVEMENT);
        $auditGeneration = (new WorkoutGeneration())
            ->setName('Audit post-fix - Strength')
            ->setTimeCap(12)
            ->setNumberOfDifferentMovements(1)
            ->setWorkoutType($workoutType)
            ->setMovementDifficulty($difficulty)
            ->setMovementGenerationType($movementGenerationType)
            ->setMovementTypes([])
            ->setAvailableImplements([])
            ->setMandatoryBodyParts([])
            ->setBannedMovements([])
            ->setMandatoryMovements([])
            ->setIsTeamWorkout(false);
        $publicGeneration = (new WorkoutGeneration())
            ->setName('Visible Strength')
            ->setTimeCap(12)
            ->setNumberOfDifferentMovements(1)
            ->setWorkoutType($workoutType)
            ->setMovementDifficulty($difficulty)
            ->setMovementGenerationType($movementGenerationType)
            ->setMovementTypes([])
            ->setAvailableImplements([])
            ->setMandatoryBodyParts([])
            ->setBannedMovements([])
            ->setMandatoryMovements([])
            ->setIsTeamWorkout(false);
        $latestGeneration = (new WorkoutGeneration())
            ->setName('Latest Strength')
            ->setTimeCap(12)
            ->setNumberOfDifferentMovements(1)
            ->setWorkoutType($workoutType)
            ->setMovementDifficulty($difficulty)
            ->setMovementGenerationType($movementGenerationType)
            ->setMovementTypes([])
            ->setAvailableImplements([])
            ->setMandatoryBodyParts([])
            ->setBannedMovements([])
            ->setMandatoryMovements([])
            ->setIsTeamWorkout(false);
        $auditWorkout = (new Workout(
            'Audit post-fix - Strength',
            "For quality:\n5 Back Squats",
            5,
            12,
            $workoutType,
            $origin,
        ))->setWorkoutGeneration($auditGeneration);
        $publicWorkout = (new Workout(
            'Visible Strength',
            "For quality:\n5 Back Squats",
            5,
            12,
            $workoutType,
            $origin,
        ))->setWorkoutGeneration($publicGeneration);
        $latestWorkout = (new Workout(
            'Latest Strength',
            "For quality:\n5 Back Squats",
            5,
            12,
            $workoutType,
            $origin,
        ))->setWorkoutGeneration($latestGeneration);
        $internalSourceWorkout = (new Workout(
            'Internal source catalogue check',
            "For time:\n10 Burpees",
            1,
            8,
            $workoutType,
            $origin,
        ))->setSourceName('monwod_audit');

        foreach ([$origin, $workoutType, $difficulty, $movementGenerationType, $auditGeneration, $publicGeneration, $latestGeneration, $auditWorkout, $publicWorkout, $latestWorkout, $internalSourceWorkout] as $entity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();
        $entityManager->clear();

        $this->browser()->request('GET', '/api/workout-catalog?q=strength&includeDuplicates=true&itemsPerPage=1000');

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $workouts = $payload['member'] ?? $payload['hydra:member'] ?? [];
        $names = array_map(static fn (array $workout): ?string => $workout['name'] ?? null, $workouts);

        self::assertContains('Visible Strength', $names);
        self::assertContains('Latest Strength', $names);
        self::assertNotContains('Audit post-fix - Strength', $names);

        $this->browser()->request('GET', '/api/workout-catalog?name=internal%20source%20catalogue%20check&includeDuplicates=true&itemsPerPage=1000');

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(0, $payload['totalItems']);

        $this->browser()->request('GET', '/api/workouts?name=Audit%20post-fix%20-%20Strength&itemsPerPage=1000');

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $workouts = $payload['member'] ?? $payload['hydra:member'] ?? [];
        self::assertCount(0, $workouts);
    }

    public function testFrontendCanSearchWorkoutCatalogWithAdvancedFiltersAndMatchDetails(): void
    {
        $this->browser()->request(
            'GET',
            '/api/workout-catalog?q=double&workoutType=For%20time&movement=Double%20Under&implement=Jump%20Rope&timeCapMin=30&timeCapMax=45&itemsPerPage=1000',
        );

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $workouts = $payload['member'] ?? $payload['hydra:member'] ?? [];
        $names = array_map(static fn (array $workout): ?string => $workout['name'] ?? null, $workouts);

        self::assertContains('Open 17.5', $names);
        self::assertNotContains('Fran', $names);

        $openWorkout = null;
        foreach ($workouts as $workout) {
            if (($workout['name'] ?? null) === 'Open 17.5') {
                $openWorkout = $workout;
                break;
            }
        }

        self::assertIsArray($openWorkout);
        self::assertSame(['flow'], $openWorkout['matchDetails']['query']['fields']);
        self::assertSame('For time', $openWorkout['matchDetails']['workoutType']);
        self::assertSame([
            'value' => 40,
            'requested' => null,
            'min' => 30,
            'max' => 45,
        ], $openWorkout['matchDetails']['timeCap']);
        self::assertSame(['Double Under'], $openWorkout['matchDetails']['movements']);
        self::assertSame(['jump rope'], $openWorkout['matchDetails']['implements']);
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
                'competitionId' => (string) $competition->getId(),
                'competitionName' => 'CrossFit Games Open',
                'competitionSeason' => 2017,
                'competitionLogoUrl' => null,
                'eventName' => 'Open 17.5',
                'eventOrder' => 5,
                'sourceName' => 'crossfit_games',
                'divisions' => ['Women'],
                'provenances' => [],
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
            ->setCountryName('France')
            ->setCountryCode('FR')
            ->setRegionName('Ile-de-France')
            ->setCityName('Paris')
            ->setCompetitionType('functional_fitness')
            ->setParticipationType('individual')
            ->setLogoUrl('https://example.test/french.png');
        $usaUpcoming = (new Competition('Granite Games 2026', 'competition_corner', 'fast-catalog-usa'))
            ->setStartsAt($now->modify('+2 months'))
            ->setLocationLabel('Minnesota, United States')
            ->setCountryName('United States')
            ->setCountryCode('US')
            ->setRegionName('Minnesota')
            ->setParticipationType('team');
        $pastOpen = (new Competition('CrossFit Open 2024', 'crossfit_games', 'fast-catalog-open'))
            ->setSeason(2024)
            ->setStatus('past')
            ->setLocationLabel('En ligne');
        $staleUpcoming = (new Competition('Stale Throwdown 2026', 'competition_corner', 'fast-catalog-stale'))
            ->setStatus('upcoming')
            ->setStartsAt($now->modify('-10 days'))
            ->setEndsAt($now->modify('-8 days'))
            ->setLocationLabel('Paris, France')
            ->setCountryName('France')
            ->setParticipationType('individual');
        $officialQualification = (new CompetitionOfficialQualification($franceUpcoming, 'crossfit_games', 'semifinals', 'elite'))
            ->setSeason(2026)
            ->confirm();
        $suggestedQualification = (new CompetitionOfficialQualification($staleUpcoming, 'crossfit_games', 'semifinals', 'elite'))
            ->setSeason(2026);

        foreach ([$franceUpcoming, $usaUpcoming, $pastOpen, $staleUpcoming, $officialQualification, $suggestedQualification] as $entity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();

        $this->browser()->request('GET', '/api/competition-catalog?page=1&country=France&status=upcoming&source=competition_corner&participation=individual');

        self::assertResponseIsSuccessful();
        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(1, $payload['totalItems']);
        self::assertSame('French Throwdown 2026', $payload['member'][0]['name']);
        self::assertSame('https://example.test/french.png', $payload['member'][0]['logoUrl']);
        self::assertSame('France', $payload['member'][0]['countryName']);
        self::assertSame('FR', $payload['member'][0]['countryCode']);
        self::assertSame('Ile-de-France', $payload['member'][0]['regionName']);
        self::assertSame('Paris', $payload['member'][0]['cityName']);
        self::assertSame([
            [
                'circuit' => 'crossfit_games',
                'stage' => 'semifinals',
                'divisionPattern' => 'elite',
                'season' => 2026,
                'label' => 'CrossFit Games Semifinal 2026',
            ],
        ], $payload['member'][0]['officialQualifications']);
        self::assertContains('France', $payload['countries']);
        self::assertContains('United States', $payload['countries']);
        self::assertSame(['Île-de-France'], $payload['regions']);
        self::assertNull($payload['view']['next']);
    }

    public function testCompetitionCatalogSearchesByPartialNameAndSortsPastCompetitionsNewestFirst(): void
    {
        $entityManager = $this->getEntityManager();
        $now = new \DateTimeImmutable();
        $recentPast = (new Competition('The Marseille Throwdowns 2026', 'competition_corner', 'marseille-recent-past'))
            ->setStartsAt($now->modify('-10 days'))
            ->setEndsAt($now->modify('-8 days'))
            ->setLocationLabel('Marseille, France')
            ->setCountryName('France')
            ->setCityName('Marseille');
        $olderPast = (new Competition('Marseille Winter Classic 2025', 'scoring_fit', 'marseille-older-past'))
            ->setStartsAt($now->modify('-80 days'))
            ->setEndsAt($now->modify('-78 days'))
            ->setLocationLabel('Marseille, France')
            ->setCountryName('France')
            ->setCityName('Marseille');
        $unrelatedPast = (new Competition('Paris Throwdown 2026', 'competition_corner', 'paris-past'))
            ->setStartsAt($now->modify('-5 days'))
            ->setEndsAt($now->modify('-3 days'))
            ->setLocationLabel('Paris, France')
            ->setCountryName('France')
            ->setCityName('Paris');

        foreach ([$recentPast, $olderPast, $unrelatedPast] as $competition) {
            $entityManager->persist($competition);
        }
        $entityManager->flush();

        $this->browser()->request('GET', '/api/competition-catalog?q=Marseille&status=past');

        self::assertResponseIsSuccessful();
        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $names = array_map(static fn (array $competition): string => (string) $competition['name'], $payload['member']);

        self::assertGreaterThanOrEqual(2, $payload['totalItems']);
        self::assertSame('The Marseille Throwdowns 2026', $names[0]);
        self::assertSame('Marseille Winter Classic 2025', $names[1]);
        self::assertNotContains('Paris Throwdown 2026', $names);
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
        $reversedScoringProfile = (new Athlete('Garat Océane', 'scoring_fit', 'oceane-scoring'))
            ->setFirstName('Océane')
            ->setLastName('Garat');
        $otherAthlete = new Athlete('Other Athlete', 'crossfit_games', 'other-athlete');
        $competition = (new Competition('2019 Games', 'crossfit_games', 'games-2019'))
            ->setSeason(2019)
            ->setStatus('past')
            ->setStartsAt(new \DateTimeImmutable('2019-08-01T09:00:00+00:00'))
            ->setEndsAt(new \DateTimeImmutable('2019-08-04T18:00:00+00:00'))
            ->setLocationLabel('Madison, Wisconsin')
            ->setCompetitionType('functional_fitness')
            ->setParticipationType('individual');
        $officialQualification = (new CompetitionOfficialQualification($competition, 'crossfit_games', 'semifinals', 'elite'))
            ->setSeason(2019)
            ->confirm();
        $scoringCompetition = (new Competition('Scoring Event', 'scoring_fit', 'scoring-event'))
            ->setSeason(2026);
        $event = (new CompetitionEvent($competition, 'Event 1', 'crossfit_games', 'games-2019-event-1'))
            ->setEventOrder(1)
            ->setWorkout($workout);
        $scoringEvent = new CompetitionEvent($scoringCompetition, 'Scoring WOD 1', 'scoring_fit', 'scoring-event-1');
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
        $scoringResult = (new WorkoutResult($reversedScoringProfile, $scoringEvent, new Score(ScoreTypeEnum::REPS, '205'), 'scoring_fit', 'oceane-scoring-event-1'))
            ->setRank(7)
            ->setFieldSize(80);
        $otherResult = new WorkoutResult($otherAthlete, $event, new Score(ScoreTypeEnum::TIME, '7:55'), 'crossfit_games', 'other-event-1');

        foreach ([
            $gamesProfile,
            $cornerProfile,
            $reversedScoringProfile,
            $otherAthlete,
            $competition,
            $scoringCompetition,
            $event,
            $scoringEvent,
            $division,
            $gamesResult,
            $cornerResult,
            $scoringResult,
            $otherResult,
            $officialQualification,
        ] as $entity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();
        $entityManager->clear();

        $this->browser()->request('GET', sprintf('/api/athletes/%s/result-summary', $gamesProfile->getId()));

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(3, $payload['totalItems']);
        self::assertCount(3, $payload['member']);
        $gamesPayload = array_values(array_filter(
            $payload['member'],
            static fn (array $result): bool => $result['externalId'] === 'oceane-games-event-1'
        ))[0] ?? null;
        self::assertNotNull($gamesPayload);
        self::assertSame('2019 Games', $gamesPayload['competitionDetails']['name']);
        self::assertArrayHasKey('logoUrl', $gamesPayload['competitionDetails']);
        self::assertSame('past', $gamesPayload['competitionDetails']['status']);
        self::assertSame('2019-08-01T09:00:00+00:00', $gamesPayload['competitionDetails']['startsAt']);
        self::assertSame('2019-08-04T18:00:00+00:00', $gamesPayload['competitionDetails']['endsAt']);
        self::assertSame('Madison, Wisconsin', $gamesPayload['competitionDetails']['locationLabel']);
        self::assertSame('functional_fitness', $gamesPayload['competitionDetails']['competitionType']);
        self::assertSame('individual', $gamesPayload['competitionDetails']['participationType']);
        self::assertSame([
            [
                'circuit' => 'crossfit_games',
                'stage' => 'semifinals',
                'divisionPattern' => 'elite',
                'season' => 2019,
                'label' => 'CrossFit Games Semifinal 2019',
            ],
        ], $gamesPayload['competitionDetails']['officialQualifications']);
        self::assertSame('Event 1', $gamesPayload['eventDetails']['name']);
        self::assertSame(1, $gamesPayload['eventDetails']['eventOrder']);
        self::assertSame('Open 17.5', $gamesPayload['workoutDetails']['name']);
        self::assertSame([
            'rank' => 12,
            'fieldSize' => 40,
            'label' => '12 / 40',
            'rankPercent' => 30,
            'percentile' => 70,
        ], $gamesPayload['rankContext']);
        self::assertArrayHasKey('scoreDetails', $gamesPayload);
        self::assertSame([
            'rank' => '8',
            'division' => 'Women',
            'divisionSourceId' => 'women',
            'format' => 'Individual',
            'formatSlug' => 'individual',
        ], $gamesPayload['participationDetails']);
        self::assertContains('/api/athletes/'.$cornerProfile->getId(), array_column($payload['member'], 'athlete'));
        self::assertContains('/api/athletes/'.$reversedScoringProfile->getId(), array_column($payload['member'], 'athlete'));
        self::assertNotContains('/api/athletes/'.$otherAthlete->getId(), array_column($payload['member'], 'athlete'));
    }

    public function testAthleteResultSummaryExposesHyroxCompetitionPerformanceDetails(): void
    {
        $entityManager = $this->getEntityManager();
        $athlete = new Athlete('HYROX Athlete', 'hyrox', 'hyrox-athlete-1');
        $competition = (new Competition('HYROX Paris 2026', 'hyrox', 'hyrox-paris-2026'))
            ->setCompetitionType('hyrox')
            ->setStartsAt(new \DateTimeImmutable('2026-03-08T08:00:00+00:00'))
            ->setLocationLabel('Paris, France');
        $event = (new CompetitionEvent($competition, 'HYROX Pro Women', 'hyrox', 'hyrox-paris-2026-pro-women'))
            ->setEventOrder(1);
        $division = new CompetitionDivision($competition, 'Pro Women', 'hyrox', 'hyrox-paris-2026-pro-women-division');
        $score = (new Score(ScoreTypeEnum::TIME, '1:02:05'))
            ->setDisplayValue('1:02:05')
            ->setTimeInSeconds(3725);
        $result = (new WorkoutResult($athlete, $event, $score, 'hyrox', 'hyrox-paris-2026-athlete-1'))
            ->setCompetitionDivision($division)
            ->setDivision('Pro Women')
            ->setRank(7)
            ->setFieldSize(128)
            ->setSourceUrl('https://results.hyrox.test/paris-2026/athlete-1')
            ->setPerformanceBreakdown([
                'sport' => 'hyrox',
                'totalTime' => ['display' => '1:02:05', 'seconds' => 3725],
                'segments' => [
                    [
                        'order' => 3,
                        'type' => 'roxzone',
                        'name' => 'Roxzone 1',
                        'time' => ['display' => '0:58', 'seconds' => 58],
                    ],
                    [
                        'order' => 1,
                        'type' => 'run',
                        'name' => 'Run 1',
                        'distance_meters' => 1000,
                        'time' => ['display' => '4:12', 'seconds' => 252],
                    ],
                    [
                        'order' => 2,
                        'type' => 'station',
                        'station_number' => 1,
                        'name' => 'SkiErg',
                        'distance_meters' => 1000,
                        'time' => ['display' => '4:25', 'seconds' => 265],
                    ],
                ],
            ]);

        foreach ([$athlete, $competition, $event, $division, $result] as $entity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();
        $entityManager->clear();

        $this->browser()->request('GET', sprintf('/api/athletes/%s/result-summary', $athlete->getId()));

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(1, $payload['totalItems']);

        $resultPayload = $payload['member'][0];
        self::assertSame('HYROX Paris 2026', $resultPayload['competitionDetails']['name']);
        self::assertSame('HYROX Pro Women', $resultPayload['eventDetails']['name']);
        self::assertSame('Pro Women', $resultPayload['competitionDivisionDetails']['name']);
        self::assertSame('1:02:05', $resultPayload['scoreDetails']['displayValue']);
        self::assertSame(3725, $resultPayload['scoreDetails']['timeInSeconds']);
        self::assertSame(7, $resultPayload['rank']);
        self::assertSame(128, $resultPayload['fieldSize']);
        self::assertSame('hyrox', $resultPayload['sourceName']);

        $performanceDetails = $resultPayload['performanceDetails'];
        self::assertSame('hyrox', $performanceDetails['sport']);
        self::assertSame('competition_result', $performanceDetails['resultKind']);
        self::assertSame('HYROX Paris 2026', $performanceDetails['competition']['name']);
        self::assertSame('2026-03-08T08:00:00+00:00', $performanceDetails['competition']['startsAt']);
        self::assertSame('HYROX Pro Women', $performanceDetails['event']['name']);
        self::assertSame('Pro Women', $performanceDetails['division']);
        self::assertSame(['display' => '1:02:05', 'seconds' => 3725], $performanceDetails['totalTime']);
        self::assertSame([
            ['order' => 1, 'type' => 'run', 'name' => 'Run 1'],
            ['order' => 2, 'type' => 'station', 'name' => 'SkiErg'],
            ['order' => 3, 'type' => 'roxzone', 'name' => 'Roxzone 1'],
        ], array_map(
            static fn (array $segment): array => [
                'order' => $segment['order'],
                'type' => $segment['type'],
                'name' => $segment['name'],
            ],
            $performanceDetails['segments'],
        ));
        self::assertSame(1000, $performanceDetails['segments'][0]['distanceMeters']);
        self::assertSame(['display' => '4:25', 'seconds' => 265], $performanceDetails['segments'][1]['time']);
        self::assertSame('https://results.hyrox.test/paris-2026/athlete-1', $performanceDetails['source']['url']);
    }

    public function testAthleteResultSummaryIncludesUpcomingParticipationsWithoutResults(): void
    {
        $entityManager = $this->getEntityManager();
        $athlete = new Athlete('Future Athlete', 'competition_corner', 'future-athlete');
        $futureCompetition = (new Competition('Future Throwdown', 'competition_corner', 'future-throwdown'))
            ->setStatus('upcoming')
            ->setStartsAt(new \DateTimeImmutable('+30 days'))
            ->setRegistrationUrl('https://competitioncorner.net/events/19804/details')
            ->setLocationLabel('En ligne')
            ->setIsOnline(true)
            ->setParticipationType('individual');
        $participation = (new CompetitionParticipation($athlete, $futureCompetition, 'competition_corner', 'future-throwdown:future-athlete'))
            ->setDivision('Elite Women')
            ->setDivisionSourceId('129901')
            ->setFormat('Individual')
            ->setFormatSlug('individual')
            ->setSourceUrl('https://competitioncorner.net/ff/19804/lcd/results');

        foreach ([$athlete, $futureCompetition, $participation] as $entity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();
        $entityManager->clear();

        $this->browser()->request('GET', sprintf('/api/athletes/%s/result-summary', $athlete->getId()));

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(0, $payload['totalItems']);
        self::assertCount(1, $payload['upcomingParticipations']);
        self::assertSame('Future Throwdown', $payload['upcomingParticipations'][0]['competitionDetails']['name']);
        self::assertSame('Elite Women', $payload['upcomingParticipations'][0]['division']);
        self::assertSame('Individual', $payload['upcomingParticipations'][0]['format']);
        self::assertSame('https://competitioncorner.net/events/19804/details', $payload['upcomingParticipations'][0]['competitionDetails']['registrationUrl']);
    }

    public function testAthleteResultSummaryHidesPlaceholderWorkoutFlow(): void
    {
        $entityManager = $this->getEntityManager();
        $workout = new Workout(
            'Workout WOD 1',
            'WOD 1',
            null,
            null,
            null,
            new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::OTHER), 2025),
        );
        $athlete = new Athlete('Bruno Guignard', 'competition_corner', 'bruno-corner');
        $competition = (new Competition('Marseille Throwdown 2025', 'competition_corner', '15984'))
            ->setSeason(2025);
        $event = (new CompetitionEvent($competition, 'WOD 1', 'competition_corner', '15984-workout-1'))
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

        self::assertSame('Workout WOD 1', $payload['member'][0]['workoutDetails']['name']);
        self::assertSame('WOD 1', $payload['member'][0]['eventDetails']['name']);
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
        self::assertContains('abmat', array_column($options['implements'], 'name'));

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
                    ->setGenerationPrompt('Prompt sent to OpenAI')
                    ->setAiUsage([
                        'request_type' => 'workout_generation',
                        'model' => 'gpt-5.4-mini',
                        'prompt_tokens' => 1200,
                        'completion_tokens' => 300,
                        'total_tokens' => 1500,
                        'duration_ms' => 800,
                        'status' => 'success',
                        'estimated_cost_usd' => null,
                    ]);
            }

            public function createWorkoutVariants(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getLastAiUsage(): ?array
            {
                return null;
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
        self::assertSame(1500, $adminWorkout['aiUsage']['total_tokens']);
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

            public function getLastAiUsage(): ?array
            {
                return [
                    'request_type' => 'workout_generation_variants',
                    'model' => 'gpt-5.4-mini',
                    'prompt_tokens' => 800,
                    'completion_tokens' => 200,
                    'total_tokens' => 1000,
                    'duration_ms' => 600,
                    'status' => 'success',
                    'estimated_cost_usd' => null,
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

            public function getLastAiUsage(): ?array
            {
                return [
                    'request_type' => 'workout_generation',
                    'model' => 'gpt-5.4-mini',
                    'prompt_tokens' => 700,
                    'completion_tokens' => 120,
                    'total_tokens' => 820,
                    'duration_ms' => 500,
                    'status' => 'success',
                    'estimated_cost_usd' => null,
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
        $usage = $this->getRepository(WorkoutAiGenerationUsage::class)->findOneBy(['endpoint' => WorkoutAiGenerationUsage::ENDPOINT_WORKOUT]);
        self::assertInstanceOf(WorkoutAiGenerationUsage::class, $usage);
        self::assertSame('failure', $usage->getStatus());
        self::assertTrue($usage->isQuotaCounted());
        self::assertSame(820, $usage->getTotalTokens());
    }

    public function testAnonymousWorkoutGenerationQuotaAllowsFivePerDayAndThenReturns429(): void
    {
        $this->browser()->disableReboot();
        $this->installSuccessfulWorkoutCreator();
        $draft = $this->createWorkoutGenerationDraft('Anonymous quota WOD');

        for ($i = 0; $i < 5; ++$i) {
            $this->browser()->request(
                'POST',
                sprintf('/api/workout-generation-flow/%s/workout', $draft['id']),
                [],
                [],
                ['HTTP_X_MONWOD_VISITOR_ID' => 'anonymous-quota-device']
            );

            self::assertResponseStatusCodeSame(201);
        }

        $this->browser()->request(
            'POST',
            sprintf('/api/workout-generation-flow/%s/workout', $draft['id']),
            [],
            [],
            ['HTTP_X_MONWOD_VISITOR_ID' => 'anonymous-quota-device']
        );

        self::assertResponseStatusCodeSame(429);
        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('workout_generation_quota_reached', $payload['code']);
        self::assertSame(5, $payload['quota']['limit']);
        self::assertSame(5, $payload['quota']['used']);
        self::assertSame(0, $payload['quota']['remaining']);
        self::assertFalse($payload['quota']['isAllowed']);
        self::assertSame(5, $this->getRepository(WorkoutAiGenerationUsage::class)->count(['endpoint' => WorkoutAiGenerationUsage::ENDPOINT_WORKOUT]));

        $this->browser()->request(
            'GET',
            '/api/workout-generation-flow/quota',
            [],
            [],
            ['HTTP_X_MONWOD_VISITOR_ID' => 'anonymous-quota-device']
        );

        self::assertResponseIsSuccessful();
        $quotaPayload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Europe/Paris', $quotaPayload['timezone']);
        self::assertSame(5, $quotaPayload['quota']['limit']);
        self::assertSame(5, $quotaPayload['quota']['used']);
        self::assertSame(0, $quotaPayload['quota']['remaining']);
    }

    public function testAnonymousWorkoutGenerationQuotaCannotBeBypassedByChangingVisitorIdOnSameIp(): void
    {
        $this->browser()->disableReboot();
        $this->installSuccessfulWorkoutCreator();
        $draft = $this->createWorkoutGenerationDraft('Anonymous quota stable IP WOD');

        for ($i = 0; $i < 5; ++$i) {
            $this->browser()->request(
                'POST',
                sprintf('/api/workout-generation-flow/%s/workout', $draft['id']),
                [],
                [],
                [
                    'HTTP_X_MONWOD_VISITOR_ID' => 'anonymous-quota-device-'.$i,
                    'REMOTE_ADDR' => '203.0.113.42',
                ]
            );

            self::assertResponseStatusCodeSame(201);
        }

        $this->browser()->request(
            'POST',
            sprintf('/api/workout-generation-flow/%s/workout', $draft['id']),
            [],
            [],
            [
                'HTTP_X_MONWOD_VISITOR_ID' => 'anonymous-quota-device-rotated',
                'REMOTE_ADDR' => '203.0.113.42',
            ]
        );

        self::assertResponseStatusCodeSame(429);
        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('workout_generation_quota_reached', $payload['code']);
        self::assertSame(5, $payload['quota']['used']);
        self::assertSame(0, $payload['quota']['remaining']);
    }

    public function testCorsAllowsMonwodVisitorIdHeaderForWorkoutGenerationQuota(): void
    {
        $this->browser()->request(
            'OPTIONS',
            '/api/workout-generation-flow/quota',
            [],
            [],
            [
                'HTTP_ORIGIN' => 'http://localhost:3000',
                'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
                'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'X-MonWOD-Visitor-Id, Content-Type',
            ]
        );

        $response = $this->browser()->getResponse();
        self::assertTrue($response->isSuccessful());
        self::assertStringContainsString(
            'x-monwod-visitor-id',
            strtolower((string) $response->headers->get('Access-Control-Allow-Headers'))
        );
    }

    public function testLoggedInFreeUserWorkoutGenerationQuotaAllowsTenPerDayAndThenReturns429(): void
    {
        $this->browser()->disableReboot();
        $this->installSuccessfulWorkoutCreator();
        $user = (new User('quota-user@example.com'))->setPassword('test-password');
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        $this->browser()->loginUser($user);
        $draft = $this->createWorkoutGenerationDraft('User quota WOD');

        for ($i = 0; $i < 10; ++$i) {
            $this->browser()->request('POST', sprintf('/api/workout-generation-flow/%s/workout', $draft['id']));

            self::assertResponseStatusCodeSame(201);
        }

        $this->browser()->request('POST', sprintf('/api/workout-generation-flow/%s/workout', $draft['id']));

        self::assertResponseStatusCodeSame(429);
        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(10, $payload['quota']['limit']);
        self::assertSame(10, $payload['quota']['used']);
        self::assertSame(0, $payload['quota']['remaining']);
        self::assertSame(10, $this->getRepository(WorkoutAiGenerationUsage::class)->count(['user' => $user]));
    }

    public function testAdminWorkoutGenerationQuotaIsUnlimited(): void
    {
        $this->browser()->disableReboot();
        $this->installSuccessfulWorkoutCreator();
        $admin = (new User('quota-admin@example.com'))
            ->setPassword('test-password')
            ->setRoles(['ROLE_ADMIN']);
        $this->getEntityManager()->persist($admin);
        $this->getEntityManager()->flush();
        $this->browser()->loginUser($admin);
        $draft = $this->createWorkoutGenerationDraft('Admin quota WOD');

        $this->browser()->request('POST', sprintf('/api/workout-generation-flow/%s/workout', $draft['id']));

        self::assertResponseStatusCodeSame(201);
        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertNull($payload['quota']['limit']);
        self::assertNull($payload['quota']['remaining']);
        self::assertTrue($payload['quota']['isAllowed']);
        $usage = $this->getRepository(WorkoutAiGenerationUsage::class)->findOneBy(['user' => $admin]);
        self::assertInstanceOf(WorkoutAiGenerationUsage::class, $usage);
        self::assertSame(WorkoutAiGenerationUsage::ACTOR_ADMIN, $usage->getActorType());
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

    private function installSuccessfulWorkoutCreator(): void
    {
        static::getContainer()->set(WorkoutCreatorServiceInterface::class, new class implements WorkoutCreatorServiceInterface {
            private ?array $lastUsage = null;

            public function createWorkout(WorkoutGeneration $workoutGeneration): Workout
            {
                $this->lastUsage = [
                    'request_type' => 'workout_generation',
                    'model' => 'gpt-5.4-mini',
                    'prompt_tokens' => 1000,
                    'completion_tokens' => 250,
                    'total_tokens' => 1250,
                    'duration_ms' => 700,
                    'status' => 'success',
                    'estimated_cost_usd' => null,
                ];

                return (new Workout(
                    $workoutGeneration->getName(),
                    'Generated quota flow',
                    $workoutGeneration->getNumberOfRounds(),
                    $workoutGeneration->getTimeCap(),
                    $workoutGeneration->getWorkoutType(),
                    new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::CUSTOM), 2026),
                    $workoutGeneration->getAvailableImplements()->toArray(),
                    $workoutGeneration->getMandatoryMovements()->toArray(),
                ))
                    ->setWorkoutGeneration($workoutGeneration)
                    ->setAiUsage($this->lastUsage);
            }

            public function createWorkoutVariants(WorkoutGeneration $workoutGeneration): array
            {
                return [];
            }

            public function getLastAiUsage(): ?array
            {
                return $this->lastUsage;
            }
        });
    }

    /**
     * @return array{id: string}
     */
    private function createWorkoutGenerationDraft(string $name): array
    {
        $this->browser()->request(
            'POST',
            '/api/workout-generation-flow',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => $name,
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

        return json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }
}
