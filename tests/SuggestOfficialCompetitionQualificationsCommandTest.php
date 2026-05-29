<?php

namespace App\Tests;

use App\Command\SuggestOfficialCompetitionQualificationsCommand;
use App\Entity\Competition\Competition;
use App\Entity\Competition\CompetitionOfficialQualification;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class SuggestOfficialCompetitionQualificationsCommandTest extends AbstractIntegrationTest
{
    public function testDryRunSuggestsMadFitnessFestivalSemifinalWithoutPersisting(): void
    {
        $competition = $this->persistMadFitnessFestival();
        $command = $this->getService(SuggestOfficialCompetitionQualificationsCommand::class);
        self::assertInstanceOf(SuggestOfficialCompetitionQualificationsCommand::class, $command);

        $tester = new CommandTester($command);

        self::assertSame(Command::SUCCESS, $tester->execute(['--source' => 'competition_corner']));
        self::assertStringContainsString('would_create', $tester->getDisplay());
        self::assertStringContainsString('MAD Fitness Festival 2026', $tester->getDisplay());
        self::assertNull($this->findQualification($competition));
    }

    public function testWritePersistsSuggestedEliteSemifinalQualificationOnce(): void
    {
        $competition = $this->persistMadFitnessFestival();
        $command = $this->getService(SuggestOfficialCompetitionQualificationsCommand::class);
        self::assertInstanceOf(SuggestOfficialCompetitionQualificationsCommand::class, $command);

        $tester = new CommandTester($command);

        self::assertSame(Command::SUCCESS, $tester->execute([
            '--source' => 'competition_corner',
            '--write' => true,
        ]));
        $this->getEntityManager()->clear();

        $qualification = $this->findQualification($competition);
        self::assertNotNull($qualification);
        self::assertSame('crossfit_games', $qualification->getCircuit());
        self::assertSame('semifinals', $qualification->getStage());
        self::assertSame('elite', $qualification->getDivisionPattern());
        self::assertSame(CompetitionOfficialQualification::STATUS_SUGGESTED, $qualification->getStatus());

        self::assertSame(Command::SUCCESS, $tester->execute([
            '--source' => 'competition_corner',
            '--write' => true,
        ]));
        $this->getEntityManager()->clear();

        $qualifications = $this->getRepository(CompetitionOfficialQualification::class)->findAll();
        self::assertCount(1, $qualifications);
    }

    private function persistMadFitnessFestival(): Competition
    {
        $competition = (new Competition('MAD Fitness Festival 2026', 'competition_corner', 'mad-2026'))
            ->setSeason(2026)
            ->setLocationLabel('Ciudad Real, Madrid, Spain')
            ->setCompetitionType('functional_fitness');

        $this->getEntityManager()->persist($competition);
        $this->getEntityManager()->flush();

        return $competition;
    }

    private function findQualification(Competition $competition): ?CompetitionOfficialQualification
    {
        /** @var CompetitionOfficialQualification|null $qualification */
        $qualification = $this->getRepository(CompetitionOfficialQualification::class)->findOneBy([
            'competition' => $competition,
            'circuit' => 'crossfit_games',
            'stage' => 'semifinals',
            'divisionPattern' => 'elite',
        ]);

        return $qualification;
    }
}
