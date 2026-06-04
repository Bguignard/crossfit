<?php

namespace App\Tests;

use App\Command\DispatchProgrammingGenerationRequestsCommand;
use App\Entity\Product\Enum\ProgrammingGenerationRequestStatusEnum;
use App\Entity\Product\Enum\ProgrammingGenerationTypeEnum;
use App\Entity\Product\PerformanceAnalysisRequest;
use App\Entity\Product\ProgrammingGenerationRequest;
use App\Entity\Product\ProgrammingSessionDetailRequest;
use App\Entity\Product\UserPerformanceProfile;
use App\Entity\Security\User;
use App\Services\Profile\ProgrammingGenerationRequestProcessor;
use App\Services\PythonWorker\PythonWorkerClientInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class DispatchProgrammingGenerationRequestsCommandTest extends AbstractIntegrationTest
{
    public function testQueuedIndividualProgrammingRequestIsCompletedWithWorkerResult(): void
    {
        $request = $this->persistQueuedProgrammingRequest('programming-dispatch-success@example.com');
        $worker = new FakeProgrammingGenerationWorker([
            'programming' => [
                'kind' => 'personal_programming_generation_v1',
                'overview' => 'Eight-week personal plan.',
            ],
        ]);
        $tester = new CommandTester(new DispatchProgrammingGenerationRequestsCommand(
            $this->getEntityManager(),
            new ProgrammingGenerationRequestProcessor($this->getEntityManager(), $worker)
        ));

        self::assertSame(Command::SUCCESS, $tester->execute(['--limit' => 1]));
        $this->getEntityManager()->clear();

        /** @var ProgrammingGenerationRequest|null $storedRequest */
        $storedRequest = $this->getRepository(ProgrammingGenerationRequest::class)->find($request->getId());
        self::assertNotNull($storedRequest);
        self::assertSame(ProgrammingGenerationRequestStatusEnum::COMPLETED, $storedRequest->getStatus());
        self::assertSame('Eight-week personal plan.', $storedRequest->getGeneratedProgramming()['overview']);
        self::assertNull($storedRequest->getErrorMessage());
        self::assertNotNull($storedRequest->getStartedAt());
        self::assertNotNull($storedRequest->getCompletedAt());
        self::assertSame(1, $worker->calls);
    }

    public function testWorkerFailureMarksProgrammingRequestAsFailed(): void
    {
        $request = $this->persistQueuedProgrammingRequest('programming-dispatch-failure@example.com');
        $worker = new FakeProgrammingGenerationWorker(exception: new \RuntimeException('Python worker timeout'));
        $tester = new CommandTester(new DispatchProgrammingGenerationRequestsCommand(
            $this->getEntityManager(),
            new ProgrammingGenerationRequestProcessor($this->getEntityManager(), $worker)
        ));

        self::assertSame(Command::FAILURE, $tester->execute(['--limit' => 1]));
        $this->getEntityManager()->clear();

        /** @var ProgrammingGenerationRequest|null $storedRequest */
        $storedRequest = $this->getRepository(ProgrammingGenerationRequest::class)->find($request->getId());
        self::assertNotNull($storedRequest);
        self::assertSame(ProgrammingGenerationRequestStatusEnum::FAILED, $storedRequest->getStatus());
        self::assertSame('Python worker timeout', $storedRequest->getErrorMessage());
        self::assertNotNull($storedRequest->getStartedAt());
        self::assertNotNull($storedRequest->getCompletedAt());
    }

    public function testDispatcherLeavesQueuedBoxProgrammingRequestsUntouched(): void
    {
        $boxRequest = $this->persistQueuedProgrammingRequest(
            'programming-dispatch-box@example.com',
            ProgrammingGenerationTypeEnum::BOX,
        );
        $worker = new FakeProgrammingGenerationWorker([
            'programming' => [
                'kind' => 'personal_programming_generation_v1',
            ],
        ]);
        $tester = new CommandTester(new DispatchProgrammingGenerationRequestsCommand(
            $this->getEntityManager(),
            new ProgrammingGenerationRequestProcessor($this->getEntityManager(), $worker)
        ));

        self::assertSame(Command::SUCCESS, $tester->execute(['--limit' => 1]));
        $this->getEntityManager()->clear();

        /** @var ProgrammingGenerationRequest|null $storedRequest */
        $storedRequest = $this->getRepository(ProgrammingGenerationRequest::class)->find($boxRequest->getId());
        self::assertNotNull($storedRequest);
        self::assertSame(ProgrammingGenerationRequestStatusEnum::QUEUED, $storedRequest->getStatus());
        self::assertNull($storedRequest->getStartedAt());
        self::assertSame(0, $worker->calls);
    }

    private function persistQueuedProgrammingRequest(
        string $email,
        ProgrammingGenerationTypeEnum $type = ProgrammingGenerationTypeEnum::INDIVIDUAL,
    ): ProgrammingGenerationRequest {
        $user = (new User($email))->setPassword('hashed-password');
        $performanceProfile = new UserPerformanceProfile($user);
        $request = (new ProgrammingGenerationRequest(
            $user,
            $type,
            constraints: [
                'duration_weeks' => 8,
                'sessions_per_week' => 5,
                'goal' => 'improve gymnastics endurance',
            ],
            inputSnapshot: [
                'source_analysis' => [
                    'summary' => 'Gymnastics limiter.',
                ],
            ],
        ))
            ->setPerformanceProfile($performanceProfile)
            ->markQueued(new \DateTimeImmutable('2026-06-02 10:00:00'));

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->persist($performanceProfile);
        $this->getEntityManager()->persist($request);
        $this->getEntityManager()->flush();

        return $request;
    }
}

final class FakeProgrammingGenerationWorker implements PythonWorkerClientInterface
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
        throw new \LogicException('Performance analysis is not used in this test.');
    }

    public function submitProgrammingGeneration(ProgrammingGenerationRequest $request): array
    {
        ++$this->calls;

        if ($this->exception !== null) {
            throw $this->exception;
        }

        return $this->response;
    }

    public function submitProgrammingSessionDetails(ProgrammingSessionDetailRequest $request): array
    {
        throw new \LogicException('Programming session details are not used in this test.');
    }
}
