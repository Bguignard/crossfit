<?php

namespace App\Tests;

use App\Entity\Competition\Athlete;
use App\Entity\Product\Box;
use App\Entity\Product\Enum\PerformanceMetricKeyEnum;
use App\Entity\Product\Enum\ProgrammingGenerationTypeEnum;
use App\Entity\Product\PerformanceAnalysisRequest;
use App\Entity\Product\ProgrammingGenerationRequest;
use App\Entity\Product\ProgrammingSessionDetailRequest;
use App\Entity\Product\UserAthleteProfile;
use App\Entity\Product\UserPerformanceMetric;
use App\Entity\Product\UserPerformanceProfile;
use App\Entity\Security\User;
use App\Services\PythonWorker\PythonWorkerClient;
use App\Services\PythonWorker\PythonWorkerException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Uid\Uuid;

class PythonWorkerClientTest extends TestCase
{
    public function testSubmitPerformanceAnalysisPostsTraceablePayload(): void
    {
        $user = (new User('python-analysis@example.com'))->setPassword('hashed-password');
        $athlete = new Athlete('Mat Fraser', 'crossfit_games', 'athlete-456');
        $athleteProfile = new UserAthleteProfile($user, $athlete);
        $performanceProfile = $this->buildEligibleProfile($user);
        $request = new PerformanceAnalysisRequest(
            $user,
            $performanceProfile,
            $athleteProfile,
            ['goal' => 'find weaknesses'],
            ['competition_results' => [['event' => 'Open 16.2']]],
        );

        $this->assignId($user);
        $this->assignId($athlete);
        $this->assignId($athleteProfile);
        $this->assignId($performanceProfile);
        $this->assignId($request);

        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use ($request, $user, $performanceProfile, $athleteProfile): MockResponse {
            $payload = $this->payloadFromOptions($options);

            self::assertSame('POST', $method);
            self::assertSame('https://crawler.monwod.test/internal/performance-analysis', $url);
            self::assertSame((string) $request->getId(), $payload['request_id']);
            self::assertSame((string) $user->getId(), $payload['user_id']);
            self::assertSame((string) $performanceProfile->getId(), $payload['performance_profile_id']);
            self::assertSame((string) $athleteProfile->getId(), $payload['athlete_profile_id']);
            self::assertSame('find weaknesses', $payload['parameters']['goal']);
            self::assertSame('Open 16.2', $payload['input_snapshot']['competition_results'][0]['event']);

            return new MockResponse(json_encode(['job_id' => 'analysis-job-1'], JSON_THROW_ON_ERROR));
        });
        $client = new PythonWorkerClient($httpClient, 'https://crawler.monwod.test/');

        self::assertSame(['job_id' => 'analysis-job-1'], $client->submitPerformanceAnalysis($request));
    }

    public function testSubmitProgrammingGenerationPostsConstraintsAndTargetContext(): void
    {
        $user = (new User('python-programming@example.com'))->setPassword('hashed-password');
        $box = new Box('CrossFit MonWod');
        $performanceProfile = new UserPerformanceProfile($user);
        $request = (new ProgrammingGenerationRequest(
            $user,
            ProgrammingGenerationTypeEnum::BOX,
            ['duration_weeks' => 6, 'equipment' => ['barbell', 'rower']],
            ['box' => ['name' => 'CrossFit MonWod']],
        ))
            ->setBox($box)
            ->setPerformanceProfile($performanceProfile);

        $this->assignId($user);
        $this->assignId($box);
        $this->assignId($performanceProfile);
        $this->assignId($request);

        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use ($request, $user, $box, $performanceProfile): MockResponse {
            $payload = $this->payloadFromOptions($options);

            self::assertSame('POST', $method);
            self::assertSame('https://crawler.monwod.test/internal/programming-generation', $url);
            self::assertSame((string) $request->getId(), $payload['request_id']);
            self::assertSame((string) $user->getId(), $payload['user_id']);
            self::assertSame(ProgrammingGenerationTypeEnum::BOX->value, $payload['type']);
            self::assertSame((string) $box->getId(), $payload['box_id']);
            self::assertSame((string) $performanceProfile->getId(), $payload['performance_profile_id']);
            self::assertSame(6, $payload['constraints']['duration_weeks']);
            self::assertSame('CrossFit MonWod', $payload['input_snapshot']['box']['name']);

            return new MockResponse(json_encode(['job_id' => 'programming-job-1'], JSON_THROW_ON_ERROR));
        });
        $client = new PythonWorkerClient($httpClient, 'https://crawler.monwod.test');

        self::assertSame(['job_id' => 'programming-job-1'], $client->submitProgrammingGeneration($request));
    }

    public function testSubmitProgrammingSessionDetailsPostsValidatedProgrammingContext(): void
    {
        $user = (new User('python-programming-detail@example.com'))->setPassword('hashed-password');
        $performanceProfile = new UserPerformanceProfile($user);
        $programmingRequest = (new ProgrammingGenerationRequest(
            $user,
            ProgrammingGenerationTypeEnum::INDIVIDUAL,
            ['durationWeeks' => 8, 'sessionsPerWeek' => 5],
            ['source_analysis_request' => ['summary' => 'Gymnastics limiter.']],
        ))
            ->setPerformanceProfile($performanceProfile)
            ->markCompleted([
                'overview' => 'Eight-week plan.',
            ]);
        $detailRequest = new ProgrammingSessionDetailRequest(
            $user,
            $programmingRequest,
            ['source_programming_request' => ['id' => 'programming-request-id']]
        );

        $this->assignId($user);
        $this->assignId($performanceProfile);
        $this->assignId($programmingRequest);
        $this->assignId($detailRequest);

        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use ($detailRequest, $programmingRequest, $user): MockResponse {
            $payload = $this->payloadFromOptions($options);

            self::assertSame('POST', $method);
            self::assertSame('https://crawler.monwod.test/internal/programming-session-details', $url);
            self::assertSame((string) $detailRequest->getId(), $payload['request_id']);
            self::assertSame((string) $user->getId(), $payload['user_id']);
            self::assertSame((string) $programmingRequest->getId(), $payload['programming_request_id']);
            self::assertSame(8, $payload['constraints']['durationWeeks']);
            self::assertSame('Eight-week plan.', $payload['global_programming']['overview']);
            self::assertSame('programming-request-id', $payload['input_snapshot']['source_programming_request']['id']);

            return new MockResponse(json_encode(['job_id' => 'programming-detail-job-1'], JSON_THROW_ON_ERROR));
        });
        $client = new PythonWorkerClient($httpClient, 'https://crawler.monwod.test');

        self::assertSame(['job_id' => 'programming-detail-job-1'], $client->submitProgrammingSessionDetails($detailRequest));
    }

    public function testPythonWorkerHttpErrorsAreReported(): void
    {
        $user = (new User('python-error@example.com'))->setPassword('hashed-password');
        $performanceProfile = new UserPerformanceProfile($user);
        $request = new PerformanceAnalysisRequest($user, $performanceProfile);
        $httpClient = new MockHttpClient([
            new MockResponse('{"detail":"not ready"}', ['http_code' => 503]),
        ]);
        $client = new PythonWorkerClient($httpClient, 'https://crawler.monwod.test');

        $this->expectException(PythonWorkerException::class);
        $this->expectExceptionMessage('Python worker returned HTTP 503');

        $client->submitPerformanceAnalysis($request);
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

    private function assignId(object $entity): void
    {
        $reflectionProperty = new \ReflectionProperty($entity, 'id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($entity, Uuid::v4());
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function payloadFromOptions(array $options): array
    {
        self::assertArrayHasKey('body', $options);
        self::assertIsString($options['body']);

        $payload = json_decode($options['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }
}
