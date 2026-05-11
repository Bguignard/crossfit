<?php

namespace App\Tests;

use App\DataFixtures\WorkoutData;
use App\Entity\Competition\Athlete;
use App\Entity\Product\Box;
use App\Entity\Product\BoxMembership;
use App\Entity\Product\Enum\ProgrammingGenerationTypeEnum;
use App\Entity\Product\PerformanceAnalysisRequest;
use App\Entity\Product\ProgrammingGenerationRequest;
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

        $athlete = new Athlete('Tia-Clair Toomey', 'crossfit_games', 'tia-clair-toomey');
        $athleteProfile = new UserAthleteProfile($member, $athlete);
        $performanceProfile = new UserPerformanceProfile($member);
        $analysisRequest = new PerformanceAnalysisRequest($member, $performanceProfile, $athleteProfile);
        $analysisRequest->markQueued();

        $programmingRequest = new ProgrammingGenerationRequest($member, ProgrammingGenerationTypeEnum::BOX);
        $programmingRequest->markRunning();

        $box = new Box('MonWod Box');
        $boxMembership = new BoxMembership($member, $box, BoxMembership::ROLE_OWNER);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($admin);
        $entityManager->persist($member);
        $entityManager->persist($athlete);
        $entityManager->persist($athleteProfile);
        $entityManager->persist($performanceProfile);
        $entityManager->persist($analysisRequest);
        $entityManager->persist($programmingRequest);
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

        self::assertGreaterThanOrEqual(1, $payload['athletes']['total']);
        self::assertSame(1, $payload['athletes']['by_source']['crossfit_games']);

        self::assertGreaterThanOrEqual(2, $payload['users']['total']);
        self::assertSame(1, $payload['linked_athlete_profiles']['total']);
        self::assertSame(1, $payload['performance_profiles']['total']);
        self::assertSame(1, $payload['analysis_requests']['by_status']['queued']);
        self::assertSame(1, $payload['programming_requests']['by_status']['running']);
        self::assertSame(1, $payload['programming_requests']['by_type']['box']);
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
}
