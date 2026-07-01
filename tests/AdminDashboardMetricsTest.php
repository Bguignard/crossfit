<?php

namespace App\Tests;

use App\DataFixtures\WorkoutData;
use App\Entity\Competition\Athlete;
use App\Entity\Competition\Competition;
use App\Entity\Competition\CompetitionDivision;
use App\Entity\Competition\CompetitionEvent;
use App\Entity\Competition\CompetitionOfficialQualification;
use App\Entity\Competition\Enum\ScoreTypeEnum;
use App\Entity\Competition\Score;
use App\Entity\Competition\WorkoutResult;
use App\Entity\Product\Box;
use App\Entity\Product\BoxMembership;
use App\Entity\Product\Enum\ProgrammingGenerationTypeEnum;
use App\Entity\Product\PerformanceAnalysisRequest;
use App\Entity\Product\ProgrammingGenerationRequest;
use App\Entity\Product\ProgrammingSessionDetailRequest;
use App\Entity\Product\UserAthleteProfile;
use App\Entity\Product\UserPerformanceProfile;
use App\Entity\Security\User;
use App\Entity\Workout\Workout;
use App\Entity\WorkoutGeneration\WorkoutAiGenerationUsage;

/**
 * @group integration
 */
class AdminDashboardMetricsTest extends AbstractIntegrationTest
{
    public function testAdminCanReadProductAndCrawlerMetrics(): void
    {
        $admin = new User('admin@example.com');
        $admin->setPassword('test-password');
        $admin->setRoles(['ROLE_ADMIN']);

        $member = new User('member@example.com');
        $member->setPassword('test-password');

        /** @var Workout $fran */
        $fran = $this->getReference(WorkoutData::WORKOUT_FRAN, Workout::class);
        $fran->setSourceName('crossfit_games');
        $fran->setAiUsage([
            'prompt_tokens' => 200,
            'completion_tokens' => 80,
            'total_tokens' => 280,
            'model' => 'gpt-5.4-mini',
            'request_type' => 'workout_generation',
            'duration_ms' => 1200,
            'status' => 'success',
            'estimated_cost_usd' => null,
        ]);

        $athlete = new Athlete('Tia-Clair Toomey', 'crossfit_games', 'tia-clair-toomey');
        $competition = (new Competition('CrossFit Games', 'crossfit_games', 'games-2026'))->setSeason(2026);
        $event = new CompetitionEvent($competition, 'Final', 'crossfit_games', 'games-2026-final');
        $division = new CompetitionDivision($competition, 'Women', 'crossfit_games', 'games-2026-women');
        $score = (new Score(ScoreTypeEnum::REPS, '100'))->setNumericValue(100);
        $result = (new WorkoutResult($athlete, $event, $score, 'crossfit_games', 'games-2026-final-tct'))
            ->setCompetitionDivision($division)
            ->setRank(1);
        $athleteProfile = new UserAthleteProfile($member, $athlete);
        $performanceProfile = new UserPerformanceProfile($member);
        $analysisRequest = new PerformanceAnalysisRequest($member, $performanceProfile, $athleteProfile);
        $analysisRequest->markQueued();

        $programmingRequest = new ProgrammingGenerationRequest($member, ProgrammingGenerationTypeEnum::BOX);
        $programmingRequest->markRunning();
        $completedAnalysisRequest = new PerformanceAnalysisRequest($member, $performanceProfile, $athleteProfile);
        $completedAnalysisRequest->markCompleted([
            'summary' => 'Completed analysis.',
            '_openai_usage' => [
                'prompt_tokens' => 100,
                'completion_tokens' => 50,
                'total_tokens' => 150,
            ],
        ]);
        $completedProgrammingRequest = new ProgrammingGenerationRequest($member, ProgrammingGenerationTypeEnum::INDIVIDUAL);
        $completedProgrammingRequest->markCompleted([
            'overview' => 'Completed programming.',
            '_openai_usage' => [
                'prompt_tokens' => 300,
                'completion_tokens' => 120,
                'total_tokens' => 420,
            ],
        ]);
        $completedDetailRequest = new ProgrammingSessionDetailRequest($member, $completedProgrammingRequest);
        $completedDetailRequest->markCompleted([
            'overview' => 'Completed session details.',
            '_openai_usage' => [
                'prompt_tokens' => 700,
                'completion_tokens' => 280,
                'total_tokens' => 980,
            ],
        ]);

        $box = new Box('MonWod Box');
        $boxMembership = new BoxMembership($member, $box, BoxMembership::ROLE_OWNER);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($admin);
        $entityManager->persist($member);
        $entityManager->persist($athlete);
        $entityManager->persist($competition);
        $entityManager->persist($event);
        $entityManager->persist($division);
        $entityManager->persist($result);
        $entityManager->persist($athleteProfile);
        $entityManager->persist($performanceProfile);
        $entityManager->persist($analysisRequest);
        $entityManager->persist($programmingRequest);
        $entityManager->persist($completedAnalysisRequest);
        $entityManager->persist($completedProgrammingRequest);
        $entityManager->persist($completedDetailRequest);
        $entityManager->persist($box);
        $entityManager->persist($boxMembership);
        $entityManager->flush();

        $this->browser()->loginUser($admin);
        $this->browser()->request('GET', '/api/admin/metrics');

        self::assertResponseIsSuccessful();

        $payload = json_decode((string) $this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertGreaterThanOrEqual(1, $payload['workouts']['total']);
        self::assertSame(1, $payload['workouts']['by_source']['crossfit_games']);
        self::assertArrayHasKey('manual', $payload['workouts']['by_source']);
        self::assertNotNull($payload['workouts']['latest_created_at']);

        self::assertGreaterThanOrEqual(1, $payload['athletes']['total']);
        self::assertSame(1, $payload['athletes']['by_source']['crossfit_games']);

        self::assertSame(1, $payload['competitions']['total']);
        self::assertSame(1, $payload['competition_events']['total']);
        self::assertSame(1, $payload['competition_divisions']['total']);
        self::assertSame(1, $payload['workout_results']['total']);
        self::assertSame(1, $payload['workout_results']['by_source']['crossfit_games']);

        self::assertGreaterThanOrEqual(2, $payload['users']['total']);
        self::assertSame(1, $payload['users']['admins']);
        self::assertSame(1, $payload['linked_athlete_profiles']['total']);
        self::assertSame(1, $payload['performance_profiles']['total']);
        self::assertSame(1, $payload['analysis_requests']['by_status']['queued']);
        self::assertSame(1, $payload['programming_requests']['by_status']['running']);
        self::assertSame(1, $payload['programming_requests']['by_type']['box']);
        self::assertSame([
            'total_tokens' => 1830,
            'prompt_tokens' => 1300,
            'completion_tokens' => 530,
            'by_request_type' => [
                'analysis' => [
                    'total_tokens' => 150,
                    'prompt_tokens' => 100,
                    'completion_tokens' => 50,
                    'calls' => 1,
                ],
                'programming' => [
                    'total_tokens' => 420,
                    'prompt_tokens' => 300,
                    'completion_tokens' => 120,
                    'calls' => 1,
                ],
                'programming_session_details' => [
                    'total_tokens' => 980,
                    'prompt_tokens' => 700,
                    'completion_tokens' => 280,
                    'calls' => 1,
                ],
                'workout_generation' => [
                    'total_tokens' => 280,
                    'prompt_tokens' => 200,
                    'completion_tokens' => 80,
                    'calls' => 1,
                ],
            ],
        ], $payload['ai_usage']);
        self::assertSame(1, $payload['boxes']['total']);
        self::assertSame(1, $payload['box_memberships']['total']);
    }

    public function testAdminMetricsAreRestrictedToAdmins(): void
    {
        $user = new User('member@example.com');
        $user->setPassword('test-password');

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        $this->browser()->loginUser($user);
        $this->browser()->request('GET', '/api/admin/metrics');

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCanReadAiGenerationCostMetricsByCategoryModelAndPeriod(): void
    {
        $admin = new User('ai-cost-admin@example.com');
        $admin->setPassword('test-password');
        $admin->setRoles(['ROLE_ADMIN']);

        $member = new User('ai-cost-member@example.com');
        $member->setPassword('test-password');

        $performanceProfile = new UserPerformanceProfile($member);
        $periodStart = new \DateTimeImmutable('2026-07-01T00:00:00+00:00');
        $periodEnd = new \DateTimeImmutable('2026-07-02T00:00:00+00:00');
        $inside = new \DateTimeImmutable('2026-07-01T12:00:00+00:00');
        $outside = new \DateTimeImmutable('2026-06-30T12:00:00+00:00');

        $workoutSuccess = new WorkoutAiGenerationUsage(
            WorkoutAiGenerationUsage::ACTOR_USER,
            WorkoutAiGenerationUsage::ENDPOINT_WORKOUT,
            'workout',
            'success',
            true,
            $member,
            null,
            [
                'model' => 'gpt-5-mini',
                'prompt_tokens' => 100,
                'completion_tokens' => 40,
                'total_tokens' => 140,
                'estimated_cost_usd' => '0.030000',
            ],
            null,
            $inside,
        );
        $workoutFailure = new WorkoutAiGenerationUsage(
            WorkoutAiGenerationUsage::ACTOR_USER,
            WorkoutAiGenerationUsage::ENDPOINT_WORKOUT,
            'workout',
            'failure',
            true,
            $member,
            null,
            [
                'model' => 'gpt-5-mini',
                'prompt_tokens' => 50,
                'completion_tokens' => 10,
                'total_tokens' => 60,
                'estimated_cost_usd' => '0.040000',
            ],
            'Rejected by validation.',
            $inside,
        );
        $outsideWorkoutUsage = new WorkoutAiGenerationUsage(
            WorkoutAiGenerationUsage::ACTOR_USER,
            WorkoutAiGenerationUsage::ENDPOINT_WORKOUT,
            'workout',
            'success',
            true,
            $member,
            null,
            [
                'model' => 'gpt-5-mini',
                'prompt_tokens' => 999,
                'completion_tokens' => 999,
                'total_tokens' => 1998,
                'estimated_cost_usd' => '9.990000',
            ],
            null,
            $outside,
        );

        $analysisSuccess = new PerformanceAnalysisRequest($member, $performanceProfile);
        $analysisSuccess->markCompleted([
            'summary' => 'Analysis.',
            '_openai_usage' => [
                'model' => 'gpt-5',
                'prompt_tokens' => 200,
                'completion_tokens' => 80,
                'total_tokens' => 280,
                'estimated_cost_usd' => '0.010000',
            ],
        ], $inside);
        $analysisFailure = new PerformanceAnalysisRequest($member, $performanceProfile);
        $analysisFailure->markFailed('Worker failed after dispatch.', $inside);

        $athleteProgramming = new ProgrammingGenerationRequest($member, ProgrammingGenerationTypeEnum::INDIVIDUAL);
        $athleteProgramming->markCompleted([
            'overview' => 'Athlete programming.',
            '_openai_usage' => [
                'model' => 'gpt-5-mini',
                'prompt_tokens' => 300,
                'completion_tokens' => 120,
                'total_tokens' => 420,
            ],
        ], $inside);
        $sessionDetails = new ProgrammingSessionDetailRequest($member, $athleteProgramming);
        $sessionDetails->markCompleted([
            'overview' => 'Detailed sessions.',
            '_openai_usage' => [
                'model' => 'gpt-5-mini',
                'prompt_tokens' => 30,
                'completion_tokens' => 20,
                'total_tokens' => 50,
                'estimated_cost_usd' => 0.000012,
            ],
        ], $inside);

        $boxProgramming = new ProgrammingGenerationRequest($member, ProgrammingGenerationTypeEnum::BOX);
        $boxProgramming->markCompleted([
            'overview' => 'Box programming.',
            '_openai_usage' => [
                'model' => 'gpt-5',
                'prompt_tokens' => 400,
                'completion_tokens' => 160,
                'total_tokens' => 560,
                'estimated_cost_usd' => '0.020000',
            ],
        ], $inside);

        $competitionProgramming = new ProgrammingGenerationRequest($member, ProgrammingGenerationTypeEnum::COMPETITION);
        $competitionProgramming->markFailed('Competition programming failed.', $inside);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($admin);
        $entityManager->persist($member);
        $entityManager->persist($performanceProfile);
        $entityManager->persist($workoutSuccess);
        $entityManager->persist($workoutFailure);
        $entityManager->persist($outsideWorkoutUsage);
        $entityManager->persist($analysisSuccess);
        $entityManager->persist($analysisFailure);
        $entityManager->persist($athleteProgramming);
        $entityManager->persist($sessionDetails);
        $entityManager->persist($boxProgramming);
        $entityManager->persist($competitionProgramming);
        $entityManager->flush();

        $this->browser()->loginUser($admin);
        $this->browser()->request('GET', sprintf(
            '/api/admin/ai-generation-costs?from=%s&to=%s',
            rawurlencode($periodStart->format(\DateTimeInterface::ATOM)),
            rawurlencode($periodEnd->format(\DateTimeInterface::ATOM)),
        ));

        self::assertResponseIsSuccessful();

        $payload = json_decode((string) $this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($periodStart->format(\DateTimeInterface::ATOM), $payload['period']['from']);
        self::assertSame($periodEnd->format(\DateTimeInterface::ATOM), $payload['period']['to']);

        self::assertSame(5, $payload['totals']['successfulCount']);
        self::assertSame(3, $payload['totals']['failureCount']);
        self::assertSame('0.100327', $payload['totals']['totalEstimatedCostUsd']);
        self::assertSame('0.040000', $payload['totals']['failedWithTokensEstimatedCostUsd']);
        self::assertSame(1080, $payload['totals']['tokens']['prompt']);
        self::assertSame(430, $payload['totals']['tokens']['completion']);
        self::assertSame(1510, $payload['totals']['tokens']['total']);
        self::assertSame('0.070327', $payload['totals']['byModel']['gpt-5-mini']['totalEstimatedCostUsd']);
        self::assertSame('0.030000', $payload['totals']['byModel']['gpt-5']['totalEstimatedCostUsd']);
        self::assertSame(2, $payload['totals']['byModel']['unknown']['failureCount']);

        $workoutCategory = $payload['categories']['workout_generation'];
        self::assertSame(1, $workoutCategory['successfulCount']);
        self::assertSame(1, $workoutCategory['failureCount']);
        self::assertSame('0.030000', $workoutCategory['averageSuccessfulEstimatedCostUsd']);
        self::assertSame('0.070000', $workoutCategory['totalEstimatedCostUsd']);
        self::assertSame('0.040000', $workoutCategory['failedEstimatedCostUsd']);
        self::assertSame('0.040000', $workoutCategory['failedWithTokensEstimatedCostUsd']);
        self::assertSame(['gpt-5-mini'], $workoutCategory['models']);
        self::assertSame(2, $workoutCategory['byModel']['gpt-5-mini']['knownCostCount']);

        $analysisCategory = $payload['categories']['athlete_analysis'];
        self::assertSame(1, $analysisCategory['successfulCount']);
        self::assertSame(1, $analysisCategory['failureCount']);
        self::assertSame('0.010000', $analysisCategory['averageSuccessfulEstimatedCostUsd']);
        self::assertSame(1, $analysisCategory['failureUnknownCostCount']);
        self::assertContains('unknown', $analysisCategory['models']);

        $athleteProgrammingCategory = $payload['categories']['athlete_programming'];
        self::assertSame('Programmation athlete globale', $athleteProgrammingCategory['label']);
        self::assertSame(1, $athleteProgrammingCategory['successfulCount']);
        self::assertSame(0, $athleteProgrammingCategory['failureCount']);
        self::assertSame('0.000315', $athleteProgrammingCategory['totalEstimatedCostUsd']);
        self::assertSame(0, $athleteProgrammingCategory['successfulUnknownCostCount']);
        self::assertSame(420, $athleteProgrammingCategory['tokens']['total']);

        $athleteProgrammingSessionsCategory = $payload['categories']['athlete_programming_sessions'];
        self::assertSame('Seances detaillees programmation athlete', $athleteProgrammingSessionsCategory['label']);
        self::assertSame(1, $athleteProgrammingSessionsCategory['successfulCount']);
        self::assertSame(0, $athleteProgrammingSessionsCategory['failureCount']);
        self::assertSame('0.000012', $athleteProgrammingSessionsCategory['totalEstimatedCostUsd']);
        self::assertSame(50, $athleteProgrammingSessionsCategory['tokens']['total']);
        self::assertSame(['gpt-5-mini'], $athleteProgrammingSessionsCategory['models']);

        $boxProgrammingCategory = $payload['categories']['box_programming'];
        self::assertSame(1, $boxProgrammingCategory['successfulCount']);
        self::assertSame('0.020000', $boxProgrammingCategory['averageSuccessfulEstimatedCostUsd']);
        self::assertSame(['gpt-5'], $boxProgrammingCategory['models']);

        $competitionProgrammingCategory = $payload['categories']['competition_programming'];
        self::assertSame(0, $competitionProgrammingCategory['successfulCount']);
        self::assertSame(1, $competitionProgrammingCategory['failureCount']);
        self::assertNull($competitionProgrammingCategory['totalEstimatedCostUsd']);
        self::assertNull($competitionProgrammingCategory['failedWithTokensEstimatedCostUsd']);
        self::assertSame(1, $competitionProgrammingCategory['failureUnknownCostCount']);
    }

    public function testAdminCanManageOfficialCompetitionQualifications(): void
    {
        $admin = new User('admin@example.com');
        $admin->setPassword('test-password');
        $admin->setRoles(['ROLE_ADMIN']);

        $competition = (new Competition('West Coast Classic', 'competition_corner', 'semifinal-2026'))
            ->setSeason(2026);
        $division = new CompetitionDivision($competition, 'Individual Women', 'competition_corner', 'semifinal-2026-women');
        $suggestedQualification = (new CompetitionOfficialQualification($competition, 'crossfit_games', 'semifinals', 'elite'))
            ->setSeason(2026)
            ->suggest();

        $entityManager = $this->getEntityManager();
        $entityManager->persist($admin);
        $entityManager->persist($competition);
        $entityManager->persist($division);
        $entityManager->persist($suggestedQualification);
        $entityManager->flush();

        $this->browser()->loginUser($admin);
        $this->browser()->request('GET', sprintf('/api/admin/official-qualifications/competitions/%s', $competition->getId()));

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('West Coast Classic', $payload['competition']['name']);
        self::assertSame([
            [
                'name' => 'Individual Women',
                'pattern' => 'Individual Women',
                'externalId' => 'semifinal-2026-women',
            ],
        ], $payload['divisionOptions']);
        self::assertSame('suggested', $payload['qualifications'][0]['status']);
        self::assertSame('elite', $payload['qualifications'][0]['divisionPattern']);

        $this->browser()->jsonRequest('POST', sprintf('/api/admin/official-qualifications/competitions/%s', $competition->getId()), [
            'action' => 'confirm',
            'divisionPattern' => 'Individual Women',
            'season' => 2026,
            'notes' => 'Manual Semifinal confirmation.',
        ]);

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $confirmed = $this->qualificationByPattern($payload['qualifications'], 'Individual Women');
        self::assertSame('confirmed', $confirmed['status']);
        self::assertSame('CrossFit Games Semifinal 2026', $confirmed['label']);
        self::assertSame('Manual Semifinal confirmation.', $confirmed['notes']);
        self::assertNotNull($confirmed['confirmedAt']);

        $this->browser()->request('GET', '/api/admin/official-qualifications');

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $listedQualification = $this->qualificationByPattern($payload['qualifications'], 'Individual Women');
        self::assertSame('West Coast Classic', $listedQualification['competition']['name']);
        self::assertSame((string) $competition->getId(), $listedQualification['competition']['id']);
        self::assertSame('confirmed', $listedQualification['status']);

        $this->browser()->jsonRequest('POST', sprintf('/api/admin/official-qualifications/competitions/%s', $competition->getId()), [
            'action' => 'dismiss',
            'divisionPattern' => 'Individual Women',
            'season' => 2026,
        ]);

        self::assertResponseIsSuccessful();
        $payload = json_decode((string) $this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $dismissed = $this->qualificationByPattern($payload['qualifications'], 'Individual Women');
        self::assertSame('dismissed', $dismissed['status']);
        self::assertNotNull($dismissed['dismissedAt']);
    }

    public function testAnonymousUsersCannotReadAdminMetrics(): void
    {
        $this->browser()->request('GET', '/api/admin/metrics');

        self::assertContains($this->browser()->getResponse()->getStatusCode(), [401, 403]);
    }

    /**
     * @param list<array<string, mixed>> $qualifications
     *
     * @return array<string, mixed>
     */
    private function qualificationByPattern(array $qualifications, string $divisionPattern): array
    {
        foreach ($qualifications as $qualification) {
            if (($qualification['divisionPattern'] ?? null) === $divisionPattern) {
                return $qualification;
            }
        }

        self::fail(sprintf('Qualification "%s" not found.', $divisionPattern));
    }
}
