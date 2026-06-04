<?php

namespace App\Services\Profile;

use App\Entity\Product\Enum\AnalysisRequestStatusEnum;
use App\Entity\Product\PerformanceAnalysisRequest;
use App\Services\PythonWorker\PythonWorkerClientInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class PerformanceAnalysisRequestProcessor
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PythonWorkerClientInterface $pythonWorkerClient,
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
        } catch (\Throwable $exception) {
            $request->markFailed($exception->getMessage());
        }

        $this->entityManager->flush();

        return $request->getStatus() === AnalysisRequestStatusEnum::COMPLETED;
    }
}
