<?php

namespace App\Tests;

use App\DataFixtures\WorkoutData;
use App\Entity\Competition\Athlete;
use App\Entity\Competition\Competition;
use App\Entity\Competition\CompetitionEvent;
use App\Entity\Competition\Enum\ScoreTypeEnum;
use App\Entity\Competition\Score;
use App\Entity\Competition\WorkoutResult;
use App\Entity\Workout\Workout;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

/**
 * @group integration
 */
class ImportedCompetitionModelTest extends AbstractIntegrationTest
{
    public function testImportedResultGraphCanBeMatchedBySourceIdentity(): void
    {
        /** @var Workout $openWorkout */
        $openWorkout = $this->getReference(WorkoutData::WORKOUT_OPEN_17_5, Workout::class);

        $athlete = (new Athlete('Tia-Clair Toomey', 'crossfit_games', 'athlete-123'))
            ->setFirstName('Tia-Clair')
            ->setLastName('Toomey')
            ->setCountry('Australia');
        $competition = (new Competition('CrossFit Games Open 2017', 'crossfit_games', 'open-2017'))
            ->setSeason(2017);
        $event = (new CompetitionEvent($competition, 'Open 17.5', 'crossfit_games', 'open-2017-17-5'))
            ->setEventOrder(5)
            ->setWorkout($openWorkout);
        $score = (new Score(ScoreTypeEnum::TIME, '10:21'))
            ->setDisplayValue('10:21')
            ->setTimeInSeconds(621);
        $result = (new WorkoutResult($athlete, $event, $score, 'crossfit_games', 'open-2017-17-5-tct'))
            ->setRank(1)
            ->setDivision('Women');

        $em = $this->getEntityManager();
        $em->persist($athlete);
        $em->persist($competition);
        $em->persist($event);
        $em->persist($result);
        $em->flush();
        $em->clear();

        /** @var WorkoutResult|null $storedResult */
        $storedResult = $this->getRepository(WorkoutResult::class)->findOneBy([
            'sourceName' => 'crossfit_games',
            'externalId' => 'open-2017-17-5-tct',
        ]);

        self::assertNotNull($storedResult);
        self::assertSame('Tia-Clair Toomey', $storedResult->getAthlete()->getDisplayName());
        self::assertSame('Open 17.5', $storedResult->getEvent()->getName());
        self::assertSame('Open 17.5', $storedResult->getEvent()->getWorkout()?->getName());
        self::assertSame(ScoreTypeEnum::TIME, $storedResult->getScore()->getType());
        self::assertSame(621, $storedResult->getScore()->getTimeInSeconds());
    }

    public function testSourceIdentityProtectsFutureIdempotentImports(): void
    {
        $em = $this->getEntityManager();
        $em->persist(new Athlete('Mat Fraser', 'crossfit_games', 'athlete-456'));
        $em->flush();

        $this->expectException(UniqueConstraintViolationException::class);

        $em->persist(new Athlete('Matthew Fraser', 'crossfit_games', 'athlete-456'));
        $em->flush();
    }

    public function testScoresKeepRawDisplayAndNormalizedValuesTogether(): void
    {
        $score = (new Score(ScoreTypeEnum::LOAD, '315'))
            ->setDisplayValue('315 lb')
            ->setNumericValue(315.0)
            ->setUnit('lb');

        $em = $this->getEntityManager();
        $em->persist($score);
        $em->flush();
        $em->clear();

        /** @var Score|null $storedScore */
        $storedScore = $this->getRepository(Score::class)->findOneBy(['rawValue' => '315']);

        self::assertNotNull($storedScore);
        self::assertSame(ScoreTypeEnum::LOAD, $storedScore->getType());
        self::assertSame('315', $storedScore->getRawValue());
        self::assertSame('315 lb', $storedScore->getDisplayValue());
        self::assertSame(315.0, $storedScore->getNumericValue());
        self::assertSame('lb', $storedScore->getUnit());
    }
}
