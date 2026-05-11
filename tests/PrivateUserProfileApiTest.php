<?php

namespace App\Tests;

use App\Entity\Competition\Athlete;
use App\Entity\Product\Enum\PerformanceMetricKeyEnum;
use App\Entity\Product\UserAthleteProfile;
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
        $athlete = new Athlete('Tia-Clair Toomey', 'crossfit_games', 'tia-123');
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
        self::assertTrue($payload['athleteProfile']['primaryProfile']);

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
