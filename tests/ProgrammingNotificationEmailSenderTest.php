<?php

namespace App\Tests;

use App\Entity\Product\Enum\ProgrammingGenerationTypeEnum;
use App\Entity\Product\ProgrammingGenerationRequest;
use App\Entity\Product\ProgrammingSessionDetailRequest;
use App\Entity\Security\User;
use App\Services\Profile\PlainTextPdfRenderer;
use App\Services\Profile\ProgrammingNotificationEmailSender;
use App\Services\Profile\ProgrammingPdfAttachmentBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

class ProgrammingNotificationEmailSenderTest extends TestCase
{
    public function testProgrammingReadyEmailIncludesPdfAttachment(): void
    {
        $mailer = new CapturingMailer();
        $sender = $this->buildSender($mailer);
        $request = (new ProgrammingGenerationRequest(
            (new User('athlete@example.com'))->setDisplayName('Bruno'),
            ProgrammingGenerationTypeEnum::INDIVIDUAL,
        ))->markCompleted([
            'overview' => 'Cycle 8 semaines',
            'weeks' => [
                [
                    'week' => 1,
                    'focus' => 'Gymnastics endurance',
                ],
            ],
        ]);

        $sender->sendProgrammingReady($request);

        self::assertNotNull($mailer->message);
        self::assertInstanceOf(Email::class, $mailer->message);
        self::assertSame('Ta programmation MonWod est prête', $mailer->message->getSubject());
        $mime = $mailer->message->toString();
        self::assertStringContainsString('programmation-monwod.pdf', $mime);
        self::assertStringContainsString('application/pdf', $mime);
    }

    public function testSessionDetailsReadyEmailIncludesPdfAttachment(): void
    {
        $mailer = new CapturingMailer();
        $sender = $this->buildSender($mailer);
        $user = new User('athlete@example.com');
        $programmingRequest = (new ProgrammingGenerationRequest(
            $user,
            ProgrammingGenerationTypeEnum::INDIVIDUAL,
        ))->markCompleted([
            'overview' => 'Cycle 8 semaines',
        ]);
        $detailRequest = (new ProgrammingSessionDetailRequest($user, $programmingRequest))->markCompleted([
            'weeks' => [
                [
                    'week' => 1,
                    'sessions' => [
                        [
                            'title' => 'Pulling endurance',
                        ],
                    ],
                ],
            ],
        ]);

        $sender->sendSessionDetailsReady($detailRequest);

        self::assertNotNull($mailer->message);
        self::assertInstanceOf(Email::class, $mailer->message);
        self::assertSame('Tes séances détaillées MonWod sont prêtes', $mailer->message->getSubject());
        $mime = $mailer->message->toString();
        self::assertStringContainsString('seances-detaillees-monwod.pdf', $mime);
        self::assertStringContainsString('application/pdf', $mime);
    }

    public function testCurrentSessionEmailIncludesPdfAttachment(): void
    {
        $mailer = new CapturingMailer();
        $sender = $this->buildSender($mailer);
        $user = new User('athlete@example.com');
        $programmingRequest = (new ProgrammingGenerationRequest(
            $user,
            ProgrammingGenerationTypeEnum::INDIVIDUAL,
        ))->markCompleted([
            'overview' => 'Cycle 8 semaines',
        ]);
        $detailRequest = (new ProgrammingSessionDetailRequest($user, $programmingRequest))->markCompleted([
            'weeks' => [
                [
                    'week' => 1,
                    'sessions' => [
                        [
                            'title' => 'Pulling endurance',
                        ],
                    ],
                ],
            ],
        ]);

        $sender->sendCurrentSession($detailRequest, [
            'week' => 1,
            'session' => 1,
            'title' => 'Pulling endurance',
        ]);

        self::assertNotNull($mailer->message);
        self::assertInstanceOf(Email::class, $mailer->message);
        self::assertSame('Ta séance du jour MonWod', $mailer->message->getSubject());
        $mime = $mailer->message->toString();
        self::assertStringContainsString('seance-du-jour-monwod.pdf', $mime);
        self::assertStringContainsString('application/pdf', $mime);
    }

    public function testPlainTextPdfRendererProducesPdfDocument(): void
    {
        $pdf = (new PlainTextPdfRenderer())->render("Programmation MonWod\n\nSemaine 1");

        self::assertStringStartsWith('%PDF-1.4', $pdf);
        self::assertStringContainsString('/Type /Catalog', $pdf);
        self::assertStringContainsString('%%EOF', $pdf);
    }

    private function buildSender(CapturingMailer $mailer): ProgrammingNotificationEmailSender
    {
        return new ProgrammingNotificationEmailSender(
            $mailer,
            new FixedUrlGenerator(),
            new ProgrammingPdfAttachmentBuilder(new PlainTextPdfRenderer()),
            'https://www.monwod.fr',
            'monwod@example.com',
        );
    }
}

final class CapturingMailer implements MailerInterface
{
    public ?RawMessage $message = null;

    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        $this->message = $message;
    }
}

final class FixedUrlGenerator implements UrlGeneratorInterface
{
    private RequestContext $context;

    public function __construct()
    {
        $this->context = new RequestContext();
    }

    public function setContext(RequestContext $context): void
    {
        $this->context = $context;
    }

    public function getContext(): RequestContext
    {
        return $this->context;
    }

    public function generate(string $name, array $parameters = [], int $referenceType = self::ABSOLUTE_PATH): string
    {
        return 'https://api.monwod.fr/api/me/requests';
    }
}
