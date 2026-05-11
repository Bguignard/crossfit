<?php

namespace App\Security;

use App\Entity\Security\UserToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class BearerTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function supports(Request $request): ?bool
    {
        return str_starts_with((string) $request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $plainToken = substr((string) $request->headers->get('Authorization'), 7);
        $tokenHash = UserToken::hash($plainToken);

        return new SelfValidatingPassport(new UserBadge($tokenHash, function (string $userIdentifier) {
            /** @var UserToken|null $token */
            $token = $this->entityManager->getRepository(UserToken::class)->findOneBy([
                'tokenHash' => $userIdentifier,
                'purpose' => UserToken::PURPOSE_API_AUTH,
                'consumedAt' => null,
            ]);

            if ($token === null || $token->isExpired()) {
                throw new AuthenticationException('Invalid bearer token.');
            }

            return $token->getUser();
        }));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['error' => 'Invalid authentication token.'], Response::HTTP_UNAUTHORIZED);
    }
}
