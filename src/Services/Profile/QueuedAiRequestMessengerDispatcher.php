<?php

namespace App\Services\Profile;

use App\Entity\Product\Enum\AnalysisRequestStatusEnum;
use App\Entity\Product\Enum\ProgrammingGenerationRequestStatusEnum;
use App\Entity\Product\Enum\ProgrammingGenerationTypeEnum;
use App\Entity\Product\PerformanceAnalysisRequest;
use App\Entity\Product\ProgrammingGenerationRequest;
use App\Entity\Security\User;
use App\Message\DispatchPerformanceAnalysisRequestMessage;
use App\Message\DispatchProgrammingGenerationRequestMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class QueuedAiRequestMessengerDispatcher
{
    private const REENQUEUE_AFTER = 'PT5M';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function enqueuePerformanceAnalysisRequest(
        PerformanceAnalysisRequest $request,
        bool $force = false,
    ): bool {
        if ($request->getStatus() !== AnalysisRequestStatusEnum::QUEUED || !$this->shouldEnqueue($request->getMessengerEnqueuedAt(), $force)) {
            return false;
        }

        $this->messageBus->dispatch(new DispatchPerformanceAnalysisRequestMessage((string) $request->getId()));
        $request->markMessengerEnqueued();
        $this->entityManager->flush();

        return true;
    }

    public function enqueueProgrammingGenerationRequest(
        ProgrammingGenerationRequest $request,
        bool $force = false,
    ): bool {
        if ($request->getStatus() !== ProgrammingGenerationRequestStatusEnum::QUEUED || !$this->shouldEnqueue($request->getMessengerEnqueuedAt(), $force)) {
            return false;
        }

        $this->messageBus->dispatch(new DispatchProgrammingGenerationRequestMessage((string) $request->getId()));
        $request->markMessengerEnqueued();
        $this->entityManager->flush();

        return true;
    }

    /**
     * @return array{analysis: int, programming: int}
     */
    public function enqueueQueuedBacklog(int $limit): array
    {
        $analysisEnqueued = 0;
        foreach ($this->queuedAnalysisRequests($limit) as $request) {
            if ($this->enqueuePerformanceAnalysisRequest($request)) {
                ++$analysisEnqueued;
            }
        }

        $programmingEnqueued = 0;
        foreach ($this->queuedProgrammingRequests($limit) as $request) {
            if ($this->enqueueProgrammingGenerationRequest($request)) {
                ++$programmingEnqueued;
            }
        }

        return [
            'analysis' => $analysisEnqueued,
            'programming' => $programmingEnqueued,
        ];
    }

    public function enqueueQueuedRequestsForUser(User $user, int $limit = 20): int
    {
        $enqueued = 0;
        foreach ($this->queuedAnalysisRequests($limit, $user) as $request) {
            if ($this->enqueuePerformanceAnalysisRequest($request)) {
                ++$enqueued;
            }
        }

        foreach ($this->queuedProgrammingRequests($limit, $user) as $request) {
            if ($this->enqueueProgrammingGenerationRequest($request)) {
                ++$enqueued;
            }
        }

        return $enqueued;
    }

    private function shouldEnqueue(?\DateTimeImmutable $messengerEnqueuedAt, bool $force): bool
    {
        if ($force || $messengerEnqueuedAt === null) {
            return true;
        }

        return $messengerEnqueuedAt->add(new \DateInterval(self::REENQUEUE_AFTER)) <= new \DateTimeImmutable();
    }

    /**
     * @return list<PerformanceAnalysisRequest>
     */
    private function queuedAnalysisRequests(int $limit, ?User $user = null): array
    {
        $queryBuilder = $this->entityManager->getRepository(PerformanceAnalysisRequest::class)
            ->createQueryBuilder('request')
            ->andWhere('request.status = :status')
            ->setParameter('status', AnalysisRequestStatusEnum::QUEUED)
            ->orderBy('request.queuedAt', 'ASC')
            ->addOrderBy('request.createdAt', 'ASC')
            ->setMaxResults($limit);

        if ($user !== null) {
            $queryBuilder
                ->andWhere('request.user = :user')
                ->setParameter('user', $user);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return list<ProgrammingGenerationRequest>
     */
    private function queuedProgrammingRequests(int $limit, ?User $user = null): array
    {
        $queryBuilder = $this->entityManager->getRepository(ProgrammingGenerationRequest::class)
            ->createQueryBuilder('request')
            ->andWhere('request.status = :status')
            ->andWhere('request.type = :type')
            ->setParameter('status', ProgrammingGenerationRequestStatusEnum::QUEUED)
            ->setParameter('type', ProgrammingGenerationTypeEnum::INDIVIDUAL)
            ->orderBy('request.queuedAt', 'ASC')
            ->addOrderBy('request.createdAt', 'ASC')
            ->setMaxResults($limit);

        if ($user !== null) {
            $queryBuilder
                ->andWhere('request.user = :user')
                ->setParameter('user', $user);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
