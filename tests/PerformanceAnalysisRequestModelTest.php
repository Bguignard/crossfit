<?php

namespace App\Tests;

use App\Entity\Competition\Athlete;
use App\Entity\Product\Enum\AnalysisRequestStatusEnum;
use App\Entity\Product\Enum\PerformanceMetricKeyEnum;
use App\Entity\Product\PerformanceAnalysisRequest;
use App\Entity\Product\UserAthleteProfile;
use App\Entity\Product\UserPerformanceMetric;
use App\Entity\Product\UserPerformanceProfile;
use App\Entity\Security\User;

class PerformanceAnalysisRequestModelTest extends AbstractIntegrationTest
{
    public function testAnalysisRequestStoresTraceableInputSnapshot(): void
    {
        $user = (new User('analysis-request@example.com'))->setPassword('hashed-password');
        $athlete = new Athlete('Tia-Clair Toomey', 'crossfit_games', 'athlete-123');
        $athleteProfile = (new UserAthleteProfile($user, $athlete))->setPrimaryProfile(true);
        $performanceProfile = $this->buildEligibleProfile($user);
        $request = new PerformanceAnalysisRequest(
            $user,
            $performanceProfile,
            $athleteProfile,
            ['goal' => 'identify weaknesses'],
            [
                'performance_metrics' => [
                    PerformanceMetricKeyEnum::BACK_SQUAT_1RM->value => 150,
                ],
                'competition_results' => [
                    ['event' => 'Open 17.5', 'rank' => 12],
                ],
            ],
        );

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->persist($athlete);
        $em->persist($athleteProfile);
        $em->persist($performanceProfile);
        $em->persist($request);
        $em->flush();
        $em->clear();

        /** @var User|null $storedUser */
        $storedUser = $this->getRepository(User::class)->findOneBy(['email' => 'analysis-request@example.com']);

        self::assertNotNull($storedUser);
        self::assertCount(1, $storedUser->getPerformanceAnalysisRequests());

        /** @var PerformanceAnalysisRequest $storedRequest */
        $storedRequest = $storedUser->getPerformanceAnalysisRequests()->first();
        self::assertSame(AnalysisRequestStatusEnum::DRAFT, $storedRequest->getStatus());
        self::assertTrue($storedRequest->wasEligibleAtCreation());
        self::assertSame(['goal' => 'identify weaknesses'], $storedRequest->getParameters());
        self::assertSame(150, $storedRequest->getInputSnapshot()['performance_metrics'][PerformanceMetricKeyEnum::BACK_SQUAT_1RM->value]);
        self::assertSame('crossfit_games', $storedRequest->getAthleteProfile()?->getAthlete()->getSourceName());
    }

    public function testAnalysisRequestLifecycleCanBeTrackedForPythonWorker(): void
    {
        $user = (new User('worker@example.com'))->setPassword('hashed-password');
        $performanceProfile = $this->buildEligibleProfile($user);
        $request = new PerformanceAnalysisRequest($user, $performanceProfile);

        $queuedAt = new \DateTimeImmutable('2026-05-10 12:00:00');
        $startedAt = new \DateTimeImmutable('2026-05-10 12:01:00');
        $completedAt = new \DateTimeImmutable('2026-05-10 12:02:00');
        $request
            ->markQueued($queuedAt)
            ->markRunning($startedAt)
            ->markCompleted([
                'strengths' => ['maximal strength'],
                'weaknesses' => ['gymnastics endurance'],
            ], $completedAt);

        self::assertSame(AnalysisRequestStatusEnum::COMPLETED, $request->getStatus());
        self::assertSame($queuedAt, $request->getQueuedAt());
        self::assertSame($startedAt, $request->getStartedAt());
        self::assertSame($completedAt, $request->getCompletedAt());
        self::assertSame(['maximal strength'], $request->getResult()['strengths']);
        self::assertNull($request->getErrorMessage());
    }

    public function testFailedAnalysisRequestKeepsErrorMessage(): void
    {
        $user = (new User('failed@example.com'))->setPassword('hashed-password');
        $performanceProfile = new UserPerformanceProfile($user);
        $request = new PerformanceAnalysisRequest($user, $performanceProfile);

        $request->markQueued()->markRunning()->markFailed('Python worker timeout');

        self::assertSame(AnalysisRequestStatusEnum::FAILED, $request->getStatus());
        self::assertFalse($request->wasEligibleAtCreation());
        self::assertSame('Python worker timeout', $request->getErrorMessage());
        self::assertNotNull($request->getCompletedAt());
    }

    private function buildEligibleProfile(User $user): UserPerformanceProfile
    {
        $profile = new UserPerformanceProfile($user);

        foreach (PerformanceMetricKeyEnum::requiredStrengthMetrics() as $metricKey) {
            (new UserPerformanceMetric($profile, $metricKey))->setNumericValue(100.0);
        }
        foreach (PerformanceMetricKeyEnum::requiredWeightliftingMetrics() as $metricKey) {
            (new UserPerformanceMetric($profile, $metricKey))->setNumericValue(80.0);
        }
        foreach (PerformanceMetricKeyEnum::gymnasticsSkillMetrics() as $metricKey) {
            (new UserPerformanceMetric($profile, $metricKey))->setBooleanValue(true);
        }
        (new UserPerformanceMetric($profile, PerformanceMetricKeyEnum::ROW_500M_TIME))->setNumericValue(95.0);
        (new UserPerformanceMetric($profile, PerformanceMetricKeyEnum::RUN_1600M_TIME))->setNumericValue(360.0);
        (new UserPerformanceMetric($profile, PerformanceMetricKeyEnum::BIKE_ERG_20MIN_WATTS))->setNumericValue(245.0);

        return $profile;
    }
}
