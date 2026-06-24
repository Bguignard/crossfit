<?php

namespace App\Services\PythonWorker;

use App\Entity\Competition\Competition;
use App\Entity\Product\PerformanceAnalysisRequest;
use App\Entity\Product\ProgrammingGenerationRequest;
use App\Entity\Product\ProgrammingSessionDetailRequest;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PythonWorkerClient implements PythonWorkerClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $pythonWorkerBaseUrl,
        private readonly int $pythonWorkerTimeoutSeconds,
    ) {
    }

    public function submitPerformanceAnalysis(PerformanceAnalysisRequest $request): array
    {
        return $this->postJson('/internal/performance-analysis', [
            'request_id' => (string) $request->getId(),
            'user_id' => (string) $request->getUser()->getId(),
            'performance_profile_id' => (string) $request->getPerformanceProfile()->getId(),
            'athlete_profile_id' => $request->getAthleteProfile()?->getId() !== null
                ? (string) $request->getAthleteProfile()->getId()
                : null,
            'parameters' => $request->getParameters(),
            'input_snapshot' => $request->getInputSnapshot(),
        ]);
    }

    public function submitProgrammingGeneration(ProgrammingGenerationRequest $request): array
    {
        return $this->postJson('/internal/programming-generation', [
            'request_id' => (string) $request->getId(),
            'user_id' => (string) $request->getUser()->getId(),
            'type' => $request->getType()->value,
            'performance_profile_id' => $request->getPerformanceProfile()?->getId() !== null
                ? (string) $request->getPerformanceProfile()->getId()
                : null,
            'coached_client_id' => $request->getCoachedClient()?->getId() !== null
                ? (string) $request->getCoachedClient()->getId()
                : null,
            'box_id' => $request->getBox()?->getId() !== null
                ? (string) $request->getBox()->getId()
                : null,
            'constraints' => $request->getConstraints(),
            'input_snapshot' => $request->getInputSnapshot(),
        ]);
    }

    public function submitProgrammingSessionDetails(ProgrammingSessionDetailRequest $request): array
    {
        $programmingRequest = $request->getProgrammingRequest();

        return $this->postJson('/internal/programming-session-details', [
            'request_id' => (string) $request->getId(),
            'user_id' => (string) $request->getUser()->getId(),
            'programming_request_id' => (string) $programmingRequest->getId(),
            'constraints' => $programmingRequest->getConstraints(),
            'global_programming' => $programmingRequest->getGeneratedProgramming(),
            'input_snapshot' => $request->getInputSnapshot(),
        ]);
    }

    public function crawlCompetitionResults(Competition $competition): array
    {
        return $this->postJson('/internal/competition-results/crawl', [
            'competition_id' => $competition->getId() !== null ? (string) $competition->getId() : null,
            'source_name' => $competition->getSourceName(),
            'external_id' => $competition->getExternalId(),
            'source_url' => $competition->getSourceUrl(),
            'name' => $competition->getName(),
            'starts_at' => $competition->getStartsAt()?->format(\DateTimeInterface::ATOM),
            'ends_at' => $competition->getEndsAt()?->format(\DateTimeInterface::ATOM),
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function postJson(string $path, array $payload): array
    {
        $url = rtrim($this->pythonWorkerBaseUrl, '/').$path;

        try {
            $response = $this->httpClient->request('POST', $url, [
                'json' => $payload,
                'timeout' => $this->pythonWorkerTimeoutSeconds,
                'max_duration' => $this->pythonWorkerTimeoutSeconds + 30,
            ]);
            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);
        } catch (TransportExceptionInterface $exception) {
            throw new PythonWorkerException('Python worker request failed: '.$exception->getMessage(), 0, $exception);
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new PythonWorkerException(sprintf('Python worker returned HTTP %d: %s', $statusCode, $content));
        }

        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new PythonWorkerException('Python worker returned invalid JSON.', 0, $exception);
        }

        if (!is_array($decoded)) {
            throw new PythonWorkerException('Python worker response must be a JSON object.');
        }

        return $decoded;
    }
}
