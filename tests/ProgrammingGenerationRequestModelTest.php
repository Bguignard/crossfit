<?php

namespace App\Tests;

use App\Entity\Product\Box;
use App\Entity\Product\CoachedClient;
use App\Entity\Product\Enum\PerformanceMetricKeyEnum;
use App\Entity\Product\Enum\ProgrammingGenerationRequestStatusEnum;
use App\Entity\Product\Enum\ProgrammingGenerationTypeEnum;
use App\Entity\Product\PerformanceAnalysisRequest;
use App\Entity\Product\ProgrammingGenerationRequest;
use App\Entity\Product\UserPerformanceMetric;
use App\Entity\Product\UserPerformanceProfile;
use App\Entity\Security\User;

class ProgrammingGenerationRequestModelTest extends AbstractIntegrationTest
{
    public function testIndividualProgrammingRequestStoresConstraintsAndProfileSnapshot(): void
    {
        $user = (new User('programming@example.com'))->setPassword('hashed-password');
        $profile = new UserPerformanceProfile($user);
        (new UserPerformanceMetric($profile, PerformanceMetricKeyEnum::BACK_SQUAT_1RM))->setNumericValue(150.0);
        $analysisRequest = (new PerformanceAnalysisRequest($user, $profile))
            ->markQueued()
            ->markRunning()
            ->markCompleted(['summary' => 'Strength baseline ready.']);
        $request = (new ProgrammingGenerationRequest(
            $user,
            ProgrammingGenerationTypeEnum::INDIVIDUAL,
            [
                'duration_weeks' => 8,
                'sessions_per_week' => 5,
                'goal' => 'improve gymnastics endurance',
                'equipment' => ['barbell', 'pull_up_bar', 'rower'],
            ],
            [
                'performance_metrics' => [
                    PerformanceMetricKeyEnum::BACK_SQUAT_1RM->value => 150,
                ],
            ],
        ))->setPerformanceProfile($profile);
        $request->setSourceAnalysisRequest($analysisRequest);

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->persist($profile);
        $em->persist($analysisRequest);
        $em->persist($request);
        $em->flush();
        $em->clear();

        /** @var User|null $storedUser */
        $storedUser = $this->getRepository(User::class)->findOneBy(['email' => 'programming@example.com']);

        self::assertNotNull($storedUser);
        self::assertCount(1, $storedUser->getProgrammingGenerationRequests());

        /** @var ProgrammingGenerationRequest $storedRequest */
        $storedRequest = $storedUser->getProgrammingGenerationRequests()->first();
        self::assertSame(ProgrammingGenerationTypeEnum::INDIVIDUAL, $storedRequest->getType());
        self::assertSame(ProgrammingGenerationRequestStatusEnum::DRAFT, $storedRequest->getStatus());
        self::assertSame(8, $storedRequest->getConstraints()['duration_weeks']);
        self::assertSame('improve gymnastics endurance', $storedRequest->getConstraints()['goal']);
        self::assertSame(150, $storedRequest->getInputSnapshot()['performance_metrics'][PerformanceMetricKeyEnum::BACK_SQUAT_1RM->value]);
        self::assertNotNull($storedRequest->getPerformanceProfile());
        self::assertSame('Strength baseline ready.', $storedRequest->getSourceAnalysisRequest()?->getResult()['summary']);
    }

    public function testBoxProgrammingRequestCanTargetABox(): void
    {
        $user = (new User('coach@example.com'))->setPassword('hashed-password');
        $box = (new Box('CrossFit MonWod'))->setSlug('crossfit-monwod');
        $request = (new ProgrammingGenerationRequest(
            $user,
            ProgrammingGenerationTypeEnum::BOX,
            [
                'duration_weeks' => 4,
                'class_levels' => ['rx', 'scaled', 'beginner'],
                'equipment' => ['rig', 'barbells', 'bike_erg'],
            ],
            [
                'box' => [
                    'name' => 'CrossFit MonWod',
                ],
            ],
        ))->setBox($box);

        $em = $this->getEntityManager();
        $em->persist($user);
        $em->persist($box);
        $em->persist($request);
        $em->flush();
        $em->clear();

        /** @var ProgrammingGenerationRequest|null $storedRequest */
        $storedRequest = $this->getRepository(ProgrammingGenerationRequest::class)->findOneBy([
            'type' => ProgrammingGenerationTypeEnum::BOX,
        ]);

        self::assertNotNull($storedRequest);
        self::assertSame('CrossFit MonWod', $storedRequest->getBox()?->getName());
        self::assertSame(['rx', 'scaled', 'beginner'], $storedRequest->getConstraints()['class_levels']);
    }

    public function testIndividualProgrammingRequestCanTargetACoachedClient(): void
    {
        $coach = (new User('client-programming-coach@example.com'))->setPassword('hashed-password');
        $client = new CoachedClient($coach, 'Client Athlete');
        $request = (new ProgrammingGenerationRequest(
            $coach,
            ProgrammingGenerationTypeEnum::INDIVIDUAL,
            [
                'duration_weeks' => 4,
                'sessions_per_week' => 3,
            ],
            [
                'coach_client' => [
                    'display_name' => 'Client Athlete',
                ],
            ],
        ))->setCoachedClient($client);

        $em = $this->getEntityManager();
        $em->persist($coach);
        $em->persist($client);
        $em->persist($request);
        $em->flush();
        $em->clear();

        /** @var ProgrammingGenerationRequest|null $storedRequest */
        $storedRequest = $this->getRepository(ProgrammingGenerationRequest::class)->find($request->getId());

        self::assertNotNull($storedRequest);
        self::assertSame('Client Athlete', $storedRequest->getCoachedClient()?->getDisplayName());
        self::assertSame((string) $coach->getId(), (string) $storedRequest->getCoachedClient()?->getCoach()->getId());
    }

    public function testCompetitionProgrammingRequestLifecycleCanBeTrackedForPythonWorker(): void
    {
        $user = (new User('competition@example.com'))->setPassword('hashed-password');
        $request = new ProgrammingGenerationRequest(
            $user,
            ProgrammingGenerationTypeEnum::COMPETITION,
            [
                'events' => 5,
                'competition_days' => 2,
                'divisions' => ['rx', 'scaled'],
            ],
        );

        $queuedAt = new \DateTimeImmutable('2026-05-11 09:00:00');
        $startedAt = new \DateTimeImmutable('2026-05-11 09:01:00');
        $completedAt = new \DateTimeImmutable('2026-05-11 09:02:00');
        $request
            ->markQueued($queuedAt)
            ->markRunning($startedAt)
            ->markCompleted([
                'events' => [
                    ['name' => 'Event 1', 'workout' => 'For time...'],
                ],
            ], $completedAt);

        self::assertSame(ProgrammingGenerationRequestStatusEnum::COMPLETED, $request->getStatus());
        self::assertSame($queuedAt, $request->getQueuedAt());
        self::assertSame($startedAt, $request->getStartedAt());
        self::assertSame($completedAt, $request->getCompletedAt());
        self::assertSame('Event 1', $request->getGeneratedProgramming()['events'][0]['name']);
        self::assertNull($request->getErrorMessage());
    }

    public function testFailedProgrammingRequestKeepsErrorMessage(): void
    {
        $user = (new User('programming-failed@example.com'))->setPassword('hashed-password');
        $request = new ProgrammingGenerationRequest($user, ProgrammingGenerationTypeEnum::INDIVIDUAL);

        $request->markQueued()->markRunning()->markFailed('Python worker timeout');

        self::assertSame(ProgrammingGenerationRequestStatusEnum::FAILED, $request->getStatus());
        self::assertSame('Python worker timeout', $request->getErrorMessage());
        self::assertNotNull($request->getCompletedAt());
    }
}
