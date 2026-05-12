<?php

namespace App\Controller\Security;

use App\Entity\Security\User;
use App\Entity\Security\UserToken;
use App\Services\Security\AuthEmailSender;
use App\Services\Security\TokenFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly TokenFactory $tokenFactory,
        private readonly AuthEmailSender $authEmailSender,
    ) {
    }

    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $payload = $this->jsonPayload($request);
        $email = $this->email($payload['email'] ?? null);
        $plainPassword = $this->string($payload['password'] ?? null);

        if ($email === null || $plainPassword === null || strlen($plainPassword) < 8) {
            return $this->json(['error' => 'Email and password of at least 8 characters are required.'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]) !== null) {
            return $this->json(['error' => 'Email is already registered.'], Response::HTTP_CONFLICT);
        }

        $user = new User($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $user->setDisplayName($this->string($payload['displayName'] ?? null));
        $this->entityManager->persist($user);

        $plainToken = $this->tokenFactory->createPlainToken();
        $this->entityManager->persist(new UserToken(
            $user,
            $plainToken,
            UserToken::PURPOSE_EMAIL_VERIFICATION,
            new \DateTimeImmutable('+48 hours'),
        ));
        $this->entityManager->flush();

        $this->authEmailSender->sendEmailVerification($user, $plainToken);

        return $this->json(['status' => 'registered'], Response::HTTP_CREATED);
    }

    #[Route('/verify-email', name: 'api_auth_verify_email', methods: ['POST', 'GET'])]
    public function verifyEmail(Request $request): JsonResponse
    {
        $plainToken = $this->tokenFromRequest($request);
        $token = $this->findUsableToken($plainToken, UserToken::PURPOSE_EMAIL_VERIFICATION);

        if ($token === null) {
            return $this->json(['error' => 'Invalid or expired token.'], Response::HTTP_BAD_REQUEST);
        }

        $token->getUser()->markEmailVerified();
        $token->consume();
        $this->entityManager->flush();

        return $this->json(['status' => 'email_verified']);
    }

    #[Route('/resend-verification', name: 'api_auth_resend_verification', methods: ['POST'])]
    public function resendVerification(Request $request): JsonResponse
    {
        $payload = $this->jsonPayload($request);
        $email = $this->email($payload['email'] ?? null);

        /** @var User|null $user */
        $user = $email !== null ? $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]) : null;
        if ($user !== null && !$user->isEmailVerified()) {
            $plainToken = $this->tokenFactory->createPlainToken();
            $this->entityManager->persist(new UserToken(
                $user,
                $plainToken,
                UserToken::PURPOSE_EMAIL_VERIFICATION,
                new \DateTimeImmutable('+48 hours'),
            ));
            $this->entityManager->flush();
            $this->authEmailSender->sendEmailVerification($user, $plainToken);
        }

        return $this->json(['status' => 'verification_email_requested']);
    }

    #[Route('/login', name: 'api_auth_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $payload = $this->jsonPayload($request);
        $email = $this->email($payload['email'] ?? null);
        $plainPassword = $this->string($payload['password'] ?? null);

        /** @var User|null $user */
        $user = $email !== null ? $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]) : null;
        if ($user === null || $plainPassword === null || !$this->passwordHasher->isPasswordValid($user, $plainPassword)) {
            return $this->json(['error' => 'Invalid credentials.'], Response::HTTP_UNAUTHORIZED);
        }
        if (!$user->isEmailVerified()) {
            return $this->json([
                'error' => 'Email must be verified before login.',
                'code' => 'email_not_verified',
            ], Response::HTTP_FORBIDDEN);
        }

        $plainToken = $this->tokenFactory->createPlainToken();
        $this->entityManager->persist(new UserToken(
            $user,
            $plainToken,
            UserToken::PURPOSE_API_AUTH,
            new \DateTimeImmutable('+30 days'),
        ));
        $this->entityManager->flush();

        return $this->json([
            'token' => $plainToken,
            'user' => $this->userPayload($user),
        ]);
    }

    #[Route('/forgot-password', name: 'api_auth_forgot_password', methods: ['POST'])]
    public function forgotPassword(Request $request): JsonResponse
    {
        $payload = $this->jsonPayload($request);
        $email = $this->email($payload['email'] ?? null);

        /** @var User|null $user */
        $user = $email !== null ? $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]) : null;
        if ($user !== null) {
            $plainToken = $this->tokenFactory->createPlainToken();
            $this->entityManager->persist(new UserToken(
                $user,
                $plainToken,
                UserToken::PURPOSE_PASSWORD_RESET,
                new \DateTimeImmutable('+1 hour'),
            ));
            $this->entityManager->flush();
            $this->authEmailSender->sendPasswordReset($user, $plainToken);
        }

        return $this->json(['status' => 'password_reset_requested']);
    }

    #[Route('/reset-password', name: 'api_auth_reset_password', methods: ['POST'])]
    public function resetPassword(Request $request): JsonResponse
    {
        $payload = $this->jsonPayload($request);
        $plainToken = $this->string($payload['token'] ?? null);
        $plainPassword = $this->string($payload['password'] ?? null);

        if ($plainPassword === null || strlen($plainPassword) < 8) {
            return $this->json(['error' => 'Password of at least 8 characters is required.'], Response::HTTP_BAD_REQUEST);
        }

        $token = $this->findUsableToken($plainToken, UserToken::PURPOSE_PASSWORD_RESET);
        if ($token === null) {
            return $this->json(['error' => 'Invalid or expired token.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $token->getUser();
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $token->consume();
        $this->entityManager->flush();

        return $this->json(['status' => 'password_reset']);
    }

    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['error' => 'Authentication required.'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json(['user' => $this->userPayload($user)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonPayload(Request $request): array
    {
        try {
            $payload = json_decode((string) $request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return is_array($payload) ? $payload : [];
    }

    private function tokenFromRequest(Request $request): ?string
    {
        if ($request->isMethod('GET')) {
            return $this->string($request->query->get('token'));
        }

        $payload = $this->jsonPayload($request);

        return $this->string($payload['token'] ?? null);
    }

    private function findUsableToken(?string $plainToken, string $purpose): ?UserToken
    {
        if ($plainToken === null) {
            return null;
        }

        /** @var UserToken|null $token */
        $token = $this->entityManager->getRepository(UserToken::class)->findOneBy([
            'tokenHash' => UserToken::hash($plainToken),
            'purpose' => $purpose,
            'consumedAt' => null,
        ]);

        return $token !== null && !$token->isExpired() ? $token : null;
    }

    private function email(mixed $value): ?string
    {
        $email = $this->string($value);
        if ($email === null || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return mb_strtolower($email);
    }

    private function string(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * @return array{id: string|null, email: string, displayName: string|null, emailVerified: bool, roles: list<string>}
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->getId()?->toRfc4122(),
            'email' => $user->getEmail(),
            'displayName' => $user->getDisplayName(),
            'emailVerified' => $user->isEmailVerified(),
            'roles' => $user->getRoles(),
        ];
    }
}
