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
