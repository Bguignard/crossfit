<?php

namespace App\Tests;

use App\Entity\Security\User;
use App\Entity\Security\UserToken;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthWorkflowTest extends AbstractIntegrationTest
{
    public function testRegistrationRequiresEmailVerificationBeforeLogin(): void
    {
        $this->jsonRequest('POST', '/api/auth/register', [
            'email' => 'Athlete@Example.com',
            'password' => 'strong-password',
            'displayName' => 'Test Athlete',
        ]);

        self::assertResponseStatusCodeSame(201);

        /** @var User|null $user */
        $user = $this->getRepository(User::class)->findOneBy(['email' => 'athlete@example.com']);
        self::assertNotNull($user);
        self::assertFalse($user->isEmailVerified());
        self::assertTrue(password_get_info($user->getPassword())['algo'] !== 0);

        $this->jsonRequest('POST', '/api/auth/login', [
            'email' => 'athlete@example.com',
            'password' => 'strong-password',
        ]);

        self::assertResponseStatusCodeSame(403);
        self::assertSame('email_not_verified', $this->jsonResponse()['code']);

        $this->jsonRequest('POST', '/api/auth/resend-verification', [
            'email' => 'athlete@example.com',
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame(['status' => 'verification_email_requested'], $this->jsonResponse());

        /** @var UserToken|null $verificationToken */
        $verificationToken = $this->getRepository(UserToken::class)->findOneBy([
            'user' => $user,
            'purpose' => UserToken::PURPOSE_EMAIL_VERIFICATION,
            'consumedAt' => null,
        ]);
        self::assertNotNull($verificationToken);
        self::assertCount(2, $this->getRepository(UserToken::class)->findBy([
            'user' => $user,
            'purpose' => UserToken::PURPOSE_EMAIL_VERIFICATION,
            'consumedAt' => null,
        ]));

        $this->jsonRequest('POST', '/api/auth/verify-email', [
            'token' => 'invalid-token',
        ]);

        self::assertResponseStatusCodeSame(400);

        $plainVerificationToken = $this->plainTokenFromHash($verificationToken);
        $this->jsonRequest('POST', '/api/auth/verify-email', [
            'token' => $plainVerificationToken,
        ]);

        self::assertResponseIsSuccessful();
        $this->getEntityManager()->clear();

        /** @var User|null $verifiedUser */
        $verifiedUser = $this->getRepository(User::class)->findOneBy(['email' => 'athlete@example.com']);
        /** @var UserToken|null $consumedVerificationToken */
        $consumedVerificationToken = $this->getRepository(UserToken::class)->find($verificationToken->getId());
        self::assertNotNull($verifiedUser);
        self::assertNotNull($consumedVerificationToken);
        self::assertTrue($verifiedUser->isEmailVerified());
        self::assertTrue($consumedVerificationToken->isConsumed());

        $this->jsonRequest('POST', '/api/auth/login', [
            'email' => 'athlete@example.com',
            'password' => 'strong-password',
        ]);

        self::assertResponseIsSuccessful();
        $payload = $this->jsonResponse();
        self::assertIsString($payload['token']);
        self::assertSame('athlete@example.com', $payload['user']['email']);
        self::assertTrue($payload['user']['emailVerified']);

        $this->browser()->request('GET', '/api/auth/me', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$payload['token'],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame('athlete@example.com', $this->jsonResponse()['user']['email']);
    }

    public function testForgotPasswordDoesNotLeakUnknownEmailsAndCanResetPassword(): void
    {
        $user = new User('reset@example.com');
        $user->setPassword('old-hash');
        $user->markEmailVerified();
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        $this->jsonRequest('POST', '/api/auth/forgot-password', [
            'email' => 'unknown@example.com',
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame(['status' => 'password_reset_requested'], $this->jsonResponse());
        self::assertEmailCount(0);

        $this->jsonRequest('POST', '/api/auth/forgot-password', [
            'email' => 'reset@example.com',
        ]);

        self::assertResponseIsSuccessful();
        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertNotNull($email);
        self::assertEmailSubjectContains($email, 'Réinitialisation');
        self::assertEmailAddressContains($email, 'To', 'reset@example.com');
        self::assertEmailTextBodyContains($email, '/reset-password?token=');

        /** @var UserToken|null $resetToken */
        $resetToken = $this->getRepository(UserToken::class)->findOneBy([
            'user' => $user,
            'purpose' => UserToken::PURPOSE_PASSWORD_RESET,
            'consumedAt' => null,
        ]);
        self::assertNotNull($resetToken);

        $this->jsonRequest('POST', '/api/auth/reset-password', [
            'token' => $this->plainTokenFromHash($resetToken),
            'password' => 'new-strong-password',
        ]);

        self::assertResponseIsSuccessful();
        $this->getEntityManager()->refresh($resetToken);
        self::assertTrue($resetToken->isConsumed());

        $this->jsonRequest('POST', '/api/auth/login', [
            'email' => 'reset@example.com',
            'password' => 'new-strong-password',
        ]);

        self::assertResponseIsSuccessful();
        self::assertIsString($this->jsonResponse()['token']);
    }

    public function testExistingUserWrongPasswordReturnsUnauthorized(): void
    {
        $user = new User('wrong-password@example.com');
        $user->setPassword($this->passwordHasher()->hashPassword($user, 'correct-password'));
        $user->markEmailVerified();
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        $this->jsonRequest('POST', '/api/auth/login', [
            'email' => 'wrong-password@example.com',
            'password' => 'wrong-password',
        ]);

        self::assertResponseStatusCodeSame(401);
        self::assertSame('Invalid credentials.', $this->jsonResponse()['error']);
        self::assertSame([], $this->getRepository(UserToken::class)->findBy([
            'user' => $user,
            'purpose' => UserToken::PURPOSE_API_AUTH,
        ]));
    }

    public function testLoginRejectsOverlyExpensivePasswordHashesBeforeVerification(): void
    {
        $user = new User('expensive-hash@example.com');
        $user->setPassword('$argon2id$v=19$m=65536,t=4,p=1$aaaaaaaaaaaaaaaaaaaaaa$bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb');
        $user->markEmailVerified();
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        $this->jsonRequest('POST', '/api/auth/login', [
            'email' => 'expensive-hash@example.com',
            'password' => 'any-password',
        ]);

        self::assertResponseStatusCodeSame(401);
        self::assertSame('Invalid credentials.', $this->jsonResponse()['error']);
        self::assertSame([], $this->getRepository(UserToken::class)->findBy([
            'user' => $user,
            'purpose' => UserToken::PURPOSE_API_AUTH,
        ]));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonRequest(string $method, string $uri, array $payload): void
    {
        $this->browser()->request($method, $uri, [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], json_encode($payload, JSON_THROW_ON_ERROR));
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

    private function passwordHasher(): UserPasswordHasherInterface
    {
        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $this->getService(UserPasswordHasherInterface::class);

        return $passwordHasher;
    }

    private function plainTokenFromHash(UserToken $token): string
    {
        $reflection = new \ReflectionClass($token);
        $property = $reflection->getProperty('tokenHash');
        $property->setAccessible(true);
        $knownToken = 'test-token';
        $property->setValue($token, UserToken::hash($knownToken));
        $this->getEntityManager()->flush();

        return $knownToken;
    }
}
