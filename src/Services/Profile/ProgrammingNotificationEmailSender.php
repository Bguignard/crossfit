<?php

namespace App\Services\Profile;

use App\Entity\Product\ProgrammingGenerationRequest;
use App\Entity\Product\ProgrammingSessionDetailRequest;
use App\Entity\Security\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class ProgrammingNotificationEmailSender implements ProgrammingNotificationSenderInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private ProgrammingPdfAttachmentBuilder $pdfAttachmentBuilder,
        private string $frontendBaseUrl,
        private string $fromEmail,
    ) {
    }

    public function sendProgrammingReady(ProgrammingGenerationRequest $request): void
    {
        $user = $request->getUser();
        $this->sendEmail(
            user: $user,
            subject: 'Ta programmation MonWod est prête',
            text: sprintf(
                "%s\n\nTa programmation personnalisée est prête. Elle est jointe à cet email au format PDF.\n\nTu peux aussi la consulter dans ton espace Analyse & programmation:\n%s\n\nMonWod\n",
                $this->buildGreeting($user),
                $this->buildFrontendUrl('/requests')
            ),
            attachmentBody: $this->pdfAttachmentBuilder->buildProgrammingPdf($request->getGeneratedProgramming() ?? []),
            attachmentName: 'programmation-monwod.pdf',
        );
    }

    public function sendSessionDetailsReady(ProgrammingSessionDetailRequest $request): void
    {
        $user = $request->getUser();
        $this->sendEmail(
            user: $user,
            subject: 'Tes séances détaillées MonWod sont prêtes',
            text: sprintf(
                "%s\n\nLes séances détaillées de ta programmation sont prêtes. Elles sont jointes à cet email au format PDF.\n\nTu peux aussi les retrouver dans ton espace Analyse & programmation:\n%s\n\nMonWod\n",
                $this->buildGreeting($user),
                $this->buildFrontendUrl('/requests')
            ),
            attachmentBody: $this->pdfAttachmentBuilder->buildSessionDetailsPdf($request->getDetailedProgramming() ?? []),
            attachmentName: 'seances-detaillees-monwod.pdf',
        );
    }

    public function sendCurrentSession(ProgrammingSessionDetailRequest $request, array $session): void
    {
        $user = $request->getUser();
        $this->sendEmail(
            user: $user,
            subject: 'Ta séance du jour MonWod',
            text: sprintf(
                "%s\n\nTa séance du jour est jointe à cet email au format PDF.\n\nTu peux aussi la retrouver dans ton espace MonWod:\n%s\n\nMonWod\n",
                $this->buildGreeting($user),
                $this->buildFrontendUrl('/seance-du-jour')
            ),
            attachmentBody: $this->pdfAttachmentBuilder->buildCurrentSessionPdf($session),
            attachmentName: 'seance-du-jour-monwod.pdf',
        );
    }

    private function buildGreeting(User $user): string
    {
        $displayName = trim((string) $user->getDisplayName());

        return $displayName === '' ? 'Bonjour,' : sprintf('Bonjour %s,', $displayName);
    }

    private function buildFrontendUrl(string $path): string
    {
        $baseUrl = rtrim($this->frontendBaseUrl, '/');
        if ($baseUrl !== '') {
            return sprintf('%s%s', $baseUrl, $path);
        }

        return $this->urlGenerator->generate('api_me_requests', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function sendEmail(
        User $user,
        string $subject,
        string $text,
        string $attachmentBody,
        string $attachmentName,
    ): void {
        $email = (new Email())
            ->from(new Address($this->fromEmail, 'MonWod'))
            ->to($user->getEmail())
            ->subject($subject)
            ->text($text)
            ->attach($attachmentBody, $attachmentName, 'application/pdf');

        $this->mailer->send($email);
    }
}
