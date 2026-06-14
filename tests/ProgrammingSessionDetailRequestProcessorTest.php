<?php

namespace App\Tests;

use App\Entity\Competition\Competition;
use App\Entity\Product\Enum\ProgrammingGenerationRequestStatusEnum;
use App\Entity\Product\Enum\ProgrammingGenerationTypeEnum;
use App\Entity\Product\PerformanceAnalysisRequest;
use App\Entity\Product\ProgrammingGenerationRequest;
use App\Entity\Product\ProgrammingSessionDetailRequest;
use App\Entity\Product\UserPerformanceProfile;
use App\Entity\Security\User;
use App\Services\Profile\ProgrammingNotificationSenderInterface;
use App\Services\Profile\ProgrammingSessionDetailRequestProcessor;
use App\Services\PythonWorker\PythonWorkerClientInterface;

class ProgrammingSessionDetailRequestProcessorTest extends AbstractIntegrationTest
{
    public function testQueuedSessionDetailRequestIsCompletedWithWorkerResult(): void
    {
        $request = $this->persistQueuedDetailRequest('programming-detail-success@example.com');
        $worker = new FakeProgrammingSessionDetailWorker([
            'detailed_programming' => [
                'weeks' => [
                    [
                        'week' => 1,
                        'sessions' => [
                            [
                                'session_key' => 'week-1-session-1',
                                'title' => 'Pulling endurance',
                                'blocks' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $notificationSender = new FakeProgrammingSessionDetailNotificationSender();
        $processor = new ProgrammingSessionDetailRequestProcessor($this->getEntityManager(), $worker, $notificationSender);

        self::assertTrue($processor->process($request));
        $this->getEntityManager()->clear();

        /** @var ProgrammingSessionDetailRequest|null $storedRequest */
        $storedRequest = $this->getRepository(ProgrammingSessionDetailRequest::class)->find($request->getId());
        self::assertNotNull($storedRequest);
        self::assertSame(ProgrammingGenerationRequestStatusEnum::COMPLETED, $storedRequest->getStatus());
        self::assertSame('Pulling endurance', $storedRequest->getDetailedProgramming()['weeks'][0]['sessions'][0]['title']);
        self::assertNull($storedRequest->getErrorMessage());
        self::assertNotNull($storedRequest->getStartedAt());
        self::assertNotNull($storedRequest->getCompletedAt());
        self::assertSame(1, $worker->calls);
        self::assertSame(1, $notificationSender->sessionDetailsReadyCalls);
    }

    public function testWorkerFailureMarksSessionDetailRequestAsFailed(): void
    {
        $request = $this->persistQueuedDetailRequest('programming-detail-failure@example.com');
        $worker = new FakeProgrammingSessionDetailWorker(exception: new \RuntimeException('Python detail worker timeout'));
        $notificationSender = new FakeProgrammingSessionDetailNotificationSender();
        $processor = new ProgrammingSessionDetailRequestProcessor($this->getEntityManager(), $worker, $notificationSender);

        self::assertFalse($processor->process($request));
        $this->getEntityManager()->clear();

        /** @var ProgrammingSessionDetailRequest|null $storedRequest */
        $storedRequest = $this->getRepository(ProgrammingSessionDetailRequest::class)->find($request->getId());
        self::assertNotNull($storedRequest);
        self::assertSame(ProgrammingGenerationRequestStatusEnum::FAILED, $storedRequest->getStatus());
        self::assertSame('Python detail worker timeout', $storedRequest->getErrorMessage());
        self::assertNotNull($storedRequest->getStartedAt());
        self::assertNotNull($storedRequest->getCompletedAt());
        self::assertSame(0, $notificationSender->sessionDetailsReadyCalls);
    }

    private function persistQueuedDetailRequest(string $email): ProgrammingSessionDetailRequest
    {
        $user = (new User($email))->setPassword('hashed-password');
        $performanceProfile = new UserPerformanceProfile($user);
        $programmingRequest = (new ProgrammingGenerationRequest(
            $user,
            ProgrammingGenerationTypeEnum::INDIVIDUAL,
            constraints: ['durationWeeks' => 8],
            inputSnapshot: ['source_analysis_request' => ['summary' => 'Gymnastics limiter.']]
        ))
            ->setPerformanceProfile($performanceProfile)
            ->markCompleted([
                'overview' => 'Eight-week personal plan.',
            ]);
        $detailRequest = (new ProgrammingSessionDetailRequest(
            $user,
            $programmingRequest,
            inputSnapshot: [
                'source_programming_request' => [
                    'id' => 'test',
                ],
            ]
        ))->markQueued(new \DateTimeImmutable('2026-06-04 12:00:00'));

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->persist($performanceProfile);
        $this->getEntityManager()->persist($programmingRequest);
        $this->getEntityManager()->persist($detailRequest);
        $this->getEntityManager()->flush();

        return $detailRequest;
    }
}

final class FakeProgrammingSessionDetailNotificationSender implements ProgrammingNotificationSenderInterface
{
    public int $sessionDetailsReadyCalls = 0;

    public function sendProgrammingReady(ProgrammingGenerationRequest $request): void
    {
        throw new \LogicException('Programming notification is not used in this test.');
    }

    public function sendSessionDetailsReady(ProgrammingSessionDetailRequest $request): void
    {
        ++$this->sessionDetailsReadyCalls;
    }

    public function sendCurrentSession(ProgrammingSessionDetailRequest $request, array $session): void
    {
        throw new \LogicException('Current session notification is not used in this test.');
    }
}

final class FakeProgrammingSessionDetailWorker implements PythonWorkerClientInterface
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
        throw new \LogicException('Programming generation is not used in this test.');
    }

    public function submitProgrammingSessionDetails(ProgrammingSessionDetailRequest $request): array
    {
        ++$this->calls;

        if ($this->exception !== null) {
            throw $this->exception;
        }

        return $this->response;
    }

    public function crawlCompetitionResults(Competition $competition): array
    {
        throw new \LogicException('Competition result crawling is not used in this test.');
    }
}
