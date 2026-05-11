<?php

namespace App\Services\Security;

use App\Entity\Security\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AuthEmailSender
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $frontendBaseUrl,
        private readonly string $fromEmail,
    ) {
    }

    public function sendEmailVerification(User $user, string $plainToken): void
    {
        $this->sendAuthEmail(
            user: $user,
            subject: 'Valide ton compte MonWod',
            text: sprintf(
                "Bienvenue sur MonWod.\n\nValide ton email avec ce lien:\n%s\n",
                $this->buildFrontendUrl('/verify-email', ['token' => $plainToken])
            ),
        );
    }

    public function sendPasswordReset(User $user, string $plainToken): void
    {
        $this->sendAuthEmail(
            user: $user,
            subject: 'Réinitialisation de ton mot de passe MonWod',
            text: sprintf(
                "Tu peux réinitialiser ton mot de passe avec ce lien:\n%s\n",
                $this->buildFrontendUrl('/reset-password', ['token' => $plainToken])
            ),
        );
    }

    /**
     * @param array<string, string> $query
     */
    private function buildFrontendUrl(string $path, array $query): string
    {
        $baseUrl = rtrim($this->frontendBaseUrl, '/');
        if ($baseUrl === '') {
            return $this->urlGenerator->generate(
                $path === '/verify-email' ? 'api_auth_verify_email' : 'api_auth_reset_password',
                $query,
                UrlGeneratorInterface::ABSOLUTE_URL,
            );
        }

        return sprintf('%s%s?%s', $baseUrl, $path, http_build_query($query));
    }

    private function sendAuthEmail(User $user, string $subject, string $text): void
    {
        $email = (new Email())
            ->from(new Address($this->fromEmail, 'MonWod'))
            ->to($user->getEmail())
            ->subject($subject)
            ->text($text);

        $this->mailer->send($email);
    }
}
