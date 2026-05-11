<?php

namespace App\Services\PythonWorker;

use App\Entity\Product\PerformanceAnalysisRequest;
use App\Entity\Product\ProgrammingGenerationRequest;

interface PythonWorkerClientInterface
{
    /**
     * @return array<string, mixed>
     */
    public function submitPerformanceAnalysis(PerformanceAnalysisRequest $request): array;

    /**
     * @return array<string, mixed>
     */
    public function submitProgrammingGeneration(ProgrammingGenerationRequest $request): array;
}
