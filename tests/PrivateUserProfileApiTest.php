<?php

namespace App\Tests;

use App\Entity\Competition\Athlete;
use App\Entity\Competition\Competition;
use App\Entity\Competition\CompetitionDivision;
use App\Entity\Competition\CompetitionEvent;
use App\Entity\Competition\Enum\ScoreTypeEnum;
use App\Entity\Competition\Score;
use App\Entity\Competition\WorkoutResult;
use App\Entity\Product\Enum\PerformanceMetricKeyEnum;
use App\Entity\Product\Enum\ProgrammingGenerationTypeEnum;
use App\Entity\Product\UserAthleteProfile;
use App\Entity\Product\UserPerformanceMetric;
use App\Entity\Product\UserPerformanceProfile;
use App\Entity\Security\User;
use App\Entity\Security\UserToken;

class PrivateUserProfileApiTest extends AbstractIntegrationTest
{
    public function testPrivateDashboardRequiresAuthentication(): void
    {
        $this->browser()->request('GET', '/api/me');

        self::assertResponseStatusCodeSame(401);
    }

    public function testUserCanLinkAthletesWithoutGlobalExclusivity(): void
    {
        [$userToken] = $this->createAuthenticatedUser('member@example.com', 'member-token');
        [$otherUserToken] = $this->createAuthenticatedUser('other@example.com', 'other-token');
        $athlete = (new Athlete('Tia-Clair Toomey', 'crossfit_games', 'tia-123'))
            ->setAvatarUrl('https://images.crossfit.test/tia.jpg');
        $this->getEntityManager()->persist($athlete);
        $this->getEntityManager()->flush();

        $this->jsonRequest('POST', '/api/me/athlete-profiles', [
            'athleteId' => (string) $athlete->getId(),
            'linkType' => UserAthleteProfile::LINK_SELF,
            'primaryProfile' => true,
        ], $userToken);

        self::assertResponseStatusCodeSame(201);
        $payload = $this->jsonResponse();
        self::assertSame('Tia-Clair Toomey', $payload['athleteProfile']['athlete']['displayName']);
        self::assertSame('https://images.crossfit.test/tia.jpg', $payload['athleteProfile']['athlete']['avatarUrl']);
        self::assertTrue($payload['athleteProfile']['primaryProfile']);

        $this->jsonRequest('GET', '/api/me', [], $userToken);

        self::assertResponseIsSuccessful();
        self::assertSame('https://images.crossfit.test/tia.jpg', $this->jsonResponse()['user']['avatarUrl']);

        $this->jsonRequest('GET', '/api/auth/me', [], $userToken);

        self::assertResponseIsSuccessful();
        self::assertSame('https://images.crossfit.test/tia.jpg', $this->jsonResponse()['user']['avatarUrl']);

        $this->jsonRequest('POST', '/api/me/athlete-profiles', [
            'athleteId' => (string) $athlete->getId(),
            'linkType' => UserAthleteProfile::LINK_FOLLOWED,
            'primaryProfile' => false,
        ], $userToken);

        self::assertResponseIsSuccessful();
        self::assertSame(UserAthleteProfile::LINK_FOLLOWED, $this->jsonResponse()['athleteProfile']['linkType']);

        $this->jsonRequest('POST', '/api/me/athlete-profiles', [
            'athleteId' => (string) $athlete->getId(),
            'linkType' => UserAthleteProfile::LINK_SELF,
        ], $otherUserToken);

        self::assertResponseStatusCodeSame(201);
        self::assertCount(2, $this->getRepository(UserAthleteProfile::class)->findBy(['athlete' => $athlete]));
    }

    public function testUserCanCreateAndUpdatePerformanceProfileMetrics(): void
    {
        [$token, $user] = $this->createAuthenticatedUser('metrics@example.com', 'metrics-token');

        $this->jsonRequest('GET', '/api/me', [], $token);

        self::assertResponseIsSuccessful();
        $dashboard = $this->jsonResponse();
        self::assertNull($dashboard['performanceProfile']);
        self::assertArrayHasKey('strength', $dashboard['performanceMetricCatalog']);

        $this->jsonRequest('PUT', '/api/me/performance-profile', [
            'metrics' => [
                [
                    'key' => PerformanceMetricKeyEnum::BACK_SQUAT_1RM->value,
                    'numericValue' => 145.5,
                    'unit' => 'kg',
                    'notes' => 'Recent single',
                ],
                [
                    'key' => PerformanceMetricKeyEnum::STRICT_PULL_UP->value,
                    'booleanValue' => true,
                ],
            ],
        ], $token);

        self::assertResponseIsSuccessful();
        $profilePayload = $this->jsonResponse()['performanceProfile'];
        self::assertFalse($profilePayload['eligibleForPerformanceAnalysis']);
        self::assertContains(PerformanceMetricKeyEnum::FRONT_SQUAT_1RM->value, $profilePayload['missingRequiredMetrics']);
        self::assertCount(2, $profilePayload['metrics']);

        $this->jsonRequest('PUT', '/api/me/performance-profile', [
            'metrics' => [
                [
                    'key' => PerformanceMetricKeyEnum::BACK_SQUAT_1RM->value,
                    'numericValue' => 150,
                    'unit' => 'kg',
                ],
            ],
        ], $token);

        self::assertResponseIsSuccessful();
        $this->getEntityManager()->clear();

        /** @var User|null $storedUser */
        $storedUser = $this->getRepository(User::class)->find($user->getId());
        self::assertNotNull($storedUser);
        self::assertCount(1, $storedUser->getPerformanceProfiles());

        /** @var UserPerformanceProfile $storedProfile */
        $storedProfile = $storedUser->getPerformanceProfiles()->first();
        self::assertSame(150.0, $storedProfile->getMetric(PerformanceMetricKeyEnum::BACK_SQUAT_1RM)?->getNumericValue());
        self::assertTrue($storedProfile->getMetric(PerformanceMetricKeyEnum::STRICT_PULL_UP)?->getBooleanValue());

        $this->jsonRequest(
            'DELETE',
            '/api/me/performance-profile/metrics/'.PerformanceMetricKeyEnum::STRICT_PULL_UP->value,
            [],
            $token
        );

        self::assertResponseIsSuccessful();
        $profilePayload = $this->jsonResponse()['performanceProfile'];
        self::assertCount(1, $profilePayload['metrics']);
        self::assertSame(PerformanceMetricKeyEnum::BACK_SQUAT_1RM->value, $profilePayload['metrics'][0]['key']);
    }

    public function testMetricPayloadIsValidatedAgainstMetricType(): void
    {
        [$token] = $this->createAuthenticatedUser('invalid-metric@example.com', 'invalid-metric-token');

        $this->jsonRequest('PUT', '/api/me/performance-profile', [
            'metrics' => [
                [
                    'key' => PerformanceMetricKeyEnum::STRICT_PULL_UP->value,
                    'numericValue' => 12,
                ],
            ],
        ], $token);

        self::assertResponseStatusCodeSame(400);
        self::assertSame(
            'Metric "strict_pull_up" expects a booleanValue.',
            $this->jsonResponse()['error']
        );
    }

    public function testUserCanCreateAnalysisAndProgrammingRequests(): void
    {
        [$token, $user] = $this->createAuthenticatedUser('requests@example.com', 'requests-token');
        $athlete = new Athlete('Bruno Athlete', 'competition_corner', 'bruno-123');
        $athleteProfile = (new UserAthleteProfile($user, $athlete))->setPrimaryProfile(true);
        $performanceProfile = new UserPerformanceProfile($user);
        (new UserPerformanceMetric($performanceProfile, PerformanceMetricKeyEnum::BACK_SQUAT_1RM))->setNumericValue(150);
        (new UserPerformanceMetric($performanceProfile, PerformanceMetricKeyEnum::STRICT_PULL_UP))->setBooleanValue(true);
        $open = (new Competition('CrossFit Open 2026', 'crossfit_games', 'open-2026'))->setSeason(2026);
        $division = new CompetitionDivision($open, 'Men', 'crossfit_games', 'open-2026-men');
        $attemptedEvent = (new CompetitionEvent($open, 'Open 26.1', 'crossfit_games', 'open-2026-1'))
            ->setEventOrder(1);
        $missedEvent = (new CompetitionEvent($open, 'Open 26.2', 'crossfit_games', 'open-2026-2'))
            ->setEventOrder(2);
        $attemptedResult = (new WorkoutResult(
            $athlete,
            $attemptedEvent,
            (new Score(ScoreTypeEnum::REPS, '200 reps'))->setNumericValue(200),
            'crossfit_games',
            'open-2026-1-bruno'
        ))
            ->setCompetitionDivision($division)
            ->setDivision('Men')
            ->setRank(30)
            ->setFieldSize(100);
        $missedResult = (new WorkoutResult(
            $athlete,
            $missedEvent,
            new Score(ScoreTypeEnum::REPS, '0 reps'),
            'crossfit_games',
            'open-2026-2-bruno'
        ))
            ->setCompetitionDivision($division)
            ->setDivision('Men')
            ->setRank(100)
            ->setFieldSize(100);

        $this->getEntityManager()->persist($athlete);
        $this->getEntityManager()->persist($athleteProfile);
        $this->getEntityManager()->persist($performanceProfile);
        $this->getEntityManager()->persist($open);
        $this->getEntityManager()->persist($division);
        $this->getEntityManager()->persist($attemptedEvent);
        $this->getEntityManager()->persist($missedEvent);
        $this->getEntityManager()->persist($attemptedResult);
        $this->getEntityManager()->persist($missedResult);
        $this->getEntityManager()->flush();

        $this->jsonRequest('POST', '/api/me/performance-analysis-requests', [
            'athleteProfileId' => (string) $athleteProfile->getId(),
            'parameters' => [
                'goal' => 'identify weaknesses',
            ],
        ], $token);

        self::assertResponseStatusCodeSame(201);
        $analysisPayload = $this->jsonResponse()['analysisRequest'];
        self::assertSame('queued', $analysisPayload['status']);
        self::assertSame('identify weaknesses', $analysisPayload['parameters']['goal']);
        self::assertSame('Bruno Athlete', $analysisPayload['athleteProfile']['athlete']['displayName']);
        self::assertSame(150, $analysisPayload['inputSnapshot']['performance_metrics'][PerformanceMetricKeyEnum::BACK_SQUAT_1RM->value]);
        self::assertSame('Open 26.1', $analysisPayload['inputSnapshot']['competition_results'][0]['event']);
        self::assertEquals(200.0, $analysisPayload['inputSnapshot']['competition_results'][0]['numeric_value']);
        self::assertSame('Open 26.2', $analysisPayload['inputSnapshot']['excluded_non_attempted_results'][0]['event']);
        self::assertSame(
            'non_attempted_or_not_submitted',
            $analysisPayload['inputSnapshot']['excluded_non_attempted_results'][0]['excluded_reason']
        );

        $this->jsonRequest('POST', '/api/me/programming-generation-requests', [
            'type' => ProgrammingGenerationTypeEnum::INDIVIDUAL->value,
            'constraints' => [
                'durationWeeks' => 8,
                'sessionsPerWeek' => 5,
                'goal' => 'gymnastics endurance',
            ],
        ], $token);

        self::assertResponseStatusCodeSame(201);
        $programmingPayload = $this->jsonResponse()['programmingRequest'];
        self::assertSame('queued', $programmingPayload['status']);
        self::assertSame(ProgrammingGenerationTypeEnum::INDIVIDUAL->value, $programmingPayload['type']);
        self::assertSame('gymnastics endurance', $programmingPayload['constraints']['goal']);
        self::assertSame(true, $programmingPayload['inputSnapshot']['performance_metrics'][PerformanceMetricKeyEnum::STRICT_PULL_UP->value]);

        $this->jsonRequest('GET', '/api/me/requests', [], $token);

        self::assertResponseIsSuccessful();
        $requestsPayload = $this->jsonResponse();
        self::assertCount(1, $requestsPayload['analysisRequests']);
        self::assertCount(1, $requestsPayload['programmingRequests']);
    }

    public function testUserCannotCreateAnotherAnalysisRequestWithinFiveMinutes(): void
    {
        [$token, $user] = $this->createAuthenticatedUser('analysis-cooldown@example.com', 'analysis-cooldown-token');
        $performanceProfile = new UserPerformanceProfile($user);
        (new UserPerformanceMetric($performanceProfile, PerformanceMetricKeyEnum::BACK_SQUAT_1RM))->setNumericValue(150);

        $this->getEntityManager()->persist($performanceProfile);
        $this->getEntityManager()->flush();

        $this->jsonRequest('POST', '/api/me/performance-analysis-requests', [
            'parameters' => [
                'goal' => 'first pass',
            ],
        ], $token);

        self::assertResponseStatusCodeSame(201);

        $this->jsonRequest('POST', '/api/me/performance-analysis-requests', [
            'parameters' => [
                'goal' => 'too soon',
            ],
        ], $token);

        self::assertResponseStatusCodeSame(429);
        $payload = $this->jsonResponse();
        self::assertSame('A recent performance analysis request already exists.', $payload['error']);
        self::assertSame('first pass', $payload['latestAnalysisRequest']['parameters']['goal']);
        self::assertArrayHasKey('nextAvailableAt', $payload);
    }

    /**
     * @return array{0: string, 1: User}
     */
    private function createAuthenticatedUser(string $email, string $plainToken): array
    {
        $user = (new User($email))->setPassword('hashed-password');
        $user->markEmailVerified();
        $token = new UserToken($user, $plainToken, UserToken::PURPOSE_API_AUTH, new \DateTimeImmutable('+1 day'));

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->persist($token);
        $this->getEntityManager()->flush();

        return [$plainToken, $user];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonRequest(string $method, string $uri, array $payload, ?string $plainToken = null): void
    {
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];
        if ($plainToken !== null) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$plainToken;
        }

        $this->browser()->request($method, $uri, [], [], $server, json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonResponse(): array
    {
        $payload = json_decode((string) $this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }
}
