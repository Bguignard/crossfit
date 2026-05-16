<?php

namespace App\Tests;

use App\Entity\Competition\Athlete;
use App\Services\Competition\AthletePublicAnalysisGenerator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;

final class AthletePublicAnalysisGeneratorTest extends TestCase
{
    public function testBlankApiKeyFailsWithExplicitConfigurationError(): void
    {
        $generator = new AthletePublicAnalysisGenerator(
            $this->createMock(EntityManagerInterface::class),
            new MockHttpClient(),
            '   ',
        );

        $method = new \ReflectionMethod($generator, 'requestAnalysis');
        $method->setAccessible(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CHAT_GPT_API_KEY is required to generate athlete public analyses.');

        $method->invoke($generator, new Athlete('Mathew Fraser', 'crossfit_games', 'mat-fraser'), [
            [
                'competition' => 'CrossFit Games',
                'season' => 2016,
                'event' => 'Final',
                'rank' => 1,
                'score' => '1st',
                'workout' => ['name' => 'Final', 'flow' => 'For time'],
            ],
        ]);
    }
}
