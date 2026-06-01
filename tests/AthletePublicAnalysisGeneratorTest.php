<?php

namespace App\Tests;

use App\Entity\Competition\Athlete;
use App\Entity\Competition\Competition;
use App\Entity\Competition\CompetitionDivision;
use App\Entity\Competition\CompetitionEvent;
use App\Entity\Competition\Enum\ScoreTypeEnum;
use App\Entity\Competition\Score;
use App\Entity\Competition\WorkoutResult;
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

    public function testAnalysisInputIgnoresOpenGhostRegistrationsRankedLastEverywhere(): void
    {
        $athlete = new Athlete('Ghost Athlete', 'crossfit_games', 'ghost-athlete');

        $open = (new Competition('CrossFit Open 2024', 'crossfit_games', 'open-2024'))->setSeason(2024);
        $division = new CompetitionDivision($open, 'Men', 'crossfit_games', 'open-2024-men');
        $openEventOne = (new CompetitionEvent($open, 'Open 24.1', 'crossfit_games', 'open-2024-1'))
            ->setEventOrder(1);
        $openEventTwo = (new CompetitionEvent($open, 'Open 24.2', 'crossfit_games', 'open-2024-2'))
            ->setEventOrder(2);
        $openResultOne = (new WorkoutResult($athlete, $openEventOne, new Score(ScoreTypeEnum::REPS, '0 reps'), 'crossfit_games', 'ghost-open-1'))
            ->setCompetitionDivision($division)
            ->setDivision('Men')
            ->setRank(100)
            ->setFieldSize(100);
        $openResultTwo = (new WorkoutResult($athlete, $openEventTwo, new Score(ScoreTypeEnum::REPS, 'DNS'), 'crossfit_games', 'ghost-open-2'))
            ->setCompetitionDivision($division)
            ->setDivision('Men')
            ->setRank(100)
            ->setFieldSize(100);

        $games = (new Competition('CrossFit Games 2024', 'crossfit_games', 'games-2024'))->setSeason(2024);
        $gamesDivision = new CompetitionDivision($games, 'Men', 'crossfit_games', 'games-2024-men');
        $gamesEvent = (new CompetitionEvent($games, 'Final', 'crossfit_games', 'games-2024-final'))
            ->setEventOrder(1);
        $gamesScore = (new Score(ScoreTypeEnum::TIME, '8:21'))->setTimeInSeconds(501);
        $gamesResult = (new WorkoutResult($athlete, $gamesEvent, $gamesScore, 'crossfit_games', 'ghost-games-final'))
            ->setCompetitionDivision($gamesDivision)
            ->setDivision('Men')
            ->setRank(10)
            ->setFieldSize(40);

        $filteredResults = $this->filterNonAttemptedQualificationResults([
            $openResultOne,
            $openResultTwo,
            $gamesResult,
        ]);

        self::assertSame([$gamesResult], $filteredResults);
    }

    public function testAnalysisInputKeepsOpenSeriesWhenAtLeastOneLastPlaceScoreIsMeaningful(): void
    {
        $athlete = new Athlete('Poor Score Athlete', 'crossfit_games', 'poor-score-athlete');
        $open = (new Competition('CrossFit Open 2025', 'crossfit_games', 'open-2025'))->setSeason(2025);
        $division = new CompetitionDivision($open, 'Women', 'crossfit_games', 'open-2025-women');
        $eventOne = (new CompetitionEvent($open, 'Open 25.1', 'crossfit_games', 'open-2025-1'))
            ->setEventOrder(1);
        $eventTwo = (new CompetitionEvent($open, 'Open 25.2', 'crossfit_games', 'open-2025-2'))
            ->setEventOrder(2);
        $missingScore = new Score(ScoreTypeEnum::REPS, '0 reps');
        $realScore = (new Score(ScoreTypeEnum::REPS, '1 rep'))->setNumericValue(1);
        $resultOne = (new WorkoutResult($athlete, $eventOne, $missingScore, 'crossfit_games', 'poor-open-1'))
            ->setCompetitionDivision($division)
            ->setDivision('Women')
            ->setRank(100)
            ->setFieldSize(100);
        $resultTwo = (new WorkoutResult($athlete, $eventTwo, $realScore, 'crossfit_games', 'poor-open-2'))
            ->setCompetitionDivision($division)
            ->setDivision('Women')
            ->setRank(100)
            ->setFieldSize(100);

        $filteredResults = $this->filterNonAttemptedQualificationResults([$resultOne, $resultTwo]);

        self::assertSame([$resultOne, $resultTwo], $filteredResults);
    }

    /**
     * @param list<WorkoutResult> $results
     *
     * @return list<WorkoutResult>
     */
    private function filterNonAttemptedQualificationResults(array $results): array
    {
        $generator = new AthletePublicAnalysisGenerator(
            $this->createMock(EntityManagerInterface::class),
            new MockHttpClient(),
            'test-key',
        );

        $method = new \ReflectionMethod($generator, 'filterNonAttemptedQualificationResults');
        $method->setAccessible(true);

        $filteredResults = $method->invoke($generator, $results);
        self::assertIsArray($filteredResults);

        return $filteredResults;
    }
}
