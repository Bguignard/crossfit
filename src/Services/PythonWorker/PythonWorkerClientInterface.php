<?php

namespace App\Services\PythonWorker;

use App\Entity\Product\PerformanceAnalysisRequest;
use App\Entity\Product\ProgrammingGenerationRequest;
use App\Entity\Product\ProgrammingSessionDetailRequest;

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

    /**
     * @return array<string, mixed>
     */
    public function submitProgrammingSessionDetails(ProgrammingSessionDetailRequest $request): array;
}
