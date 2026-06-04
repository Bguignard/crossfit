<?php

namespace App\Tests;

use App\Command\DispatchPerformanceAnalysisRequestsCommand;
use App\Entity\Product\Enum\AnalysisRequestStatusEnum;
use App\Entity\Product\Enum\PerformanceMetricKeyEnum;
use App\Entity\Product\PerformanceAnalysisRequest;
use App\Entity\Product\ProgrammingGenerationRequest;
use App\Entity\Product\ProgrammingSessionDetailRequest;
use App\Entity\Product\UserPerformanceMetric;
use App\Entity\Product\UserPerformanceProfile;
use App\Entity\Security\User;
use App\Services\Profile\PerformanceAnalysisRequestProcessor;
use App\Services\PythonWorker\PythonWorkerClientInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class DispatchPerformanceAnalysisRequestsCommandTest extends AbstractIntegrationTest
{
    public function testQueuedAnalysisRequestIsCompletedWithWorkerResult(): void
    {
        $request = $this->persistQueuedAnalysisRequest('dispatch-success@example.com');
        $worker = new FakePerformanceAnalysisWorker([
            'analysis' => [
                'kind' => 'personal_performance_analysis_v1',
                'summary' => 'Strong engine, gymnastics limiter.',
            ],
        ]);
        $tester = new CommandTester(new DispatchPerformanceAnalysisRequestsCommand(
            $this->getEntityManager(),
            new PerformanceAnalysisRequestProcessor($this->getEntityManager(), $worker)
        ));

        self::assertSame(Command::SUCCESS, $tester->execute(['--limit' => 1]));
        $this->getEntityManager()->clear();

        /** @var PerformanceAnalysisRequest|null $storedRequest */
        $storedRequest = $this->getRepository(PerformanceAnalysisRequest::class)->find($request->getId());
        self::assertNotNull($storedRequest);
        self::assertSame(AnalysisRequestStatusEnum::COMPLETED, $storedRequest->getStatus());
        self::assertSame('Strong engine, gymnastics limiter.', $storedRequest->getResult()['summary']);
        self::assertNull($storedRequest->getErrorMessage());
        self::assertNotNull($storedRequest->getStartedAt());
        self::assertNotNull($storedRequest->getCompletedAt());
        self::assertSame(1, $worker->calls);
    }

    public function testWorkerFailureMarksAnalysisRequestAsFailed(): void
    {
        $request = $this->persistQueuedAnalysisRequest('dispatch-failure@example.com');
        $worker = new FakePerformanceAnalysisWorker(exception: new \RuntimeException('Python worker timeout'));
        $tester = new CommandTester(new DispatchPerformanceAnalysisRequestsCommand(
            $this->getEntityManager(),
            new PerformanceAnalysisRequestProcessor($this->getEntityManager(), $worker)
        ));

        self::assertSame(Command::FAILURE, $tester->execute(['--limit' => 1]));
        $this->getEntityManager()->clear();

        /** @var PerformanceAnalysisRequest|null $storedRequest */
        $storedRequest = $this->getRepository(PerformanceAnalysisRequest::class)->find($request->getId());
        self::assertNotNull($storedRequest);
        self::assertSame(AnalysisRequestStatusEnum::FAILED, $storedRequest->getStatus());
        self::assertSame('Python worker timeout', $storedRequest->getErrorMessage());
        self::assertNotNull($storedRequest->getStartedAt());
        self::assertNotNull($storedRequest->getCompletedAt());
    }

    private function persistQueuedAnalysisRequest(string $email): PerformanceAnalysisRequest
    {
        $user = (new User($email))->setPassword('hashed-password');
        $performanceProfile = $this->buildEligibleProfile($user);
        $request = (new PerformanceAnalysisRequest(
            $user,
            $performanceProfile,
            parameters: ['goal' => 'identify priorities'],
            inputSnapshot: [
                'performance_metrics' => [
                    ['key' => PerformanceMetricKeyEnum::BACK_SQUAT_1RM->value, 'numericValue' => 150],
                ],
            ],
        ))->markQueued(new \DateTimeImmutable('2026-06-02 10:00:00'));

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->persist($performanceProfile);
        $this->getEntityManager()->persist($request);
        $this->getEntityManager()->flush();

        return $request;
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

final class FakePerformanceAnalysisWorker implements PythonWorkerClientInterface
{
    public int $calls = 0;

    /**
     * @param array<string, mixed> $response
     */
    public function __construct(
        private readonly array $response = [],
        private readonly ?\Throwable $exception = null,
    ) {
    }

    public function submitPerformanceAnalysis(PerformanceAnalysisRequest $request): array
    {
        ++$this->calls;

        if ($this->exception !== null) {
            throw $this->exception;
        }

        return $this->response;
    }

    public function submitProgrammingGeneration(ProgrammingGenerationRequest $request): array
    {
        throw new \LogicException('Programming generation is not used in this test.');
    }

    public function submitProgrammingSessionDetails(ProgrammingSessionDetailRequest $request): array
    {
        throw new \LogicException('Programming session details are not used in this test.');
    }
}
