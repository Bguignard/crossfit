<?php

namespace App\Services\Profile;

use App\Entity\Product\Enum\AnalysisRequestStatusEnum;
use App\Entity\Product\Enum\ProgrammingGenerationRequestStatusEnum;
use App\Entity\Product\PerformanceAnalysisRequest;
use App\Entity\Product\ProgrammingGenerationRequest;
use App\Services\PythonWorker\PythonWorkerClientInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class PerformanceAnalysisRequestProcessor
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PythonWorkerClientInterface $pythonWorkerClient,
        private ?QueuedAiRequestMessengerDispatcher $queuedAiRequestDispatcher = null,
    ) {
    }

    public function process(PerformanceAnalysisRequest $request): bool
    {
        if ($request->getStatus() !== AnalysisRequestStatusEnum::QUEUED) {
            return false;
        }

        $request->markRunning();
        $this->entityManager->flush();

        try {
            $response = $this->pythonWorkerClient->submitPerformanceAnalysis($request);
            $analysis = $response['analysis'] ?? null;

            if (!is_array($analysis)) {
                throw new \RuntimeException('Python worker response did not include an analysis object.');
            }

            $request->markCompleted($analysis);
            $this->queueDependentProgrammingRequests($request);
        } catch (\Throwable $exception) {
            $request->markFailed($exception->getMessage());
            $this->failDependentProgrammingRequests($request);
        }

        $this->entityManager->flush();

        return $request->getStatus() === AnalysisRequestStatusEnum::COMPLETED;
    }

    private function queueDependentProgrammingRequests(PerformanceAnalysisRequest $analysisRequest): void
    {
        foreach ($this->dependentProgrammingRequests($analysisRequest) as $programmingRequest) {
            $programmingRequest
                ->setInputSnapshot($this->withCompletedSourceAnalysis(
                    $programmingRequest->getInputSnapshot(),
                    $analysisRequest
                ))
                ->markQueued();
            $this->queuedAiRequestDispatcher?->enqueueProgrammingGenerationRequest($programmingRequest, force: true);
        }
    }

    private function failDependentProgrammingRequests(PerformanceAnalysisRequest $analysisRequest): void
    {
        foreach ($this->dependentProgrammingRequests($analysisRequest) as $programmingRequest) {
            $programmingRequest->markFailed('Required performance analysis failed before programming generation.');
        }
    }

    /**
     * @return list<ProgrammingGenerationRequest>
     */
    private function dependentProgrammingRequests(PerformanceAnalysisRequest $analysisRequest): array
    {
        return $this->entityManager->getRepository(ProgrammingGenerationRequest::class)
            ->createQueryBuilder('request')
            ->andWhere('request.sourceAnalysisRequest = :analysisRequest')
            ->andWhere('request.status = :status')
            ->setParameter('analysisRequest', $analysisRequest)
            ->setParameter('status', ProgrammingGenerationRequestStatusEnum::WAITING_ANALYSIS)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array<string, mixed> $inputSnapshot
     *
     * @return array<string, mixed>
     */
    private function withCompletedSourceAnalysis(array $inputSnapshot, PerformanceAnalysisRequest $analysisRequest): array
    {
        $sourceAnalysisRequest = $inputSnapshot['source_analysis_request'] ?? [];
        if (!is_array($sourceAnalysisRequest)) {
            $sourceAnalysisRequest = [];
        }

        return [
            ...$inputSnapshot,
            'analysis_dependency' => [
                'mode' => 'generated',
                'status' => 'completed',
                'source_analysis_request_id' => (string) $analysisRequest->getId(),
            ],
            'source_analysis_request' => [
                ...$sourceAnalysisRequest,
                'id' => (string) $analysisRequest->getId(),
                'status' => $analysisRequest->getStatus()->value,
                'parameters' => $analysisRequest->getParameters(),
                'input_snapshot' => $analysisRequest->getInputSnapshot(),
                'result' => $analysisRequest->getResult(),
                'created_at' => $analysisRequest->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'completed_at' => $analysisRequest->getCompletedAt()?->format(\DateTimeInterface::ATOM),
            ],
        ];
    }
}
