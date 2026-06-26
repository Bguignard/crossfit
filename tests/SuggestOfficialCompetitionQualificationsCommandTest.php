<?php

namespace App\Tests;

use App\Command\SetOfficialCompetitionQualificationCommand;
use App\Command\SuggestOfficialCompetitionQualificationsCommand;
use App\Entity\Competition\Competition;
use App\Entity\Competition\CompetitionOfficialQualification;
use App\Entity\Security\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group integration
 */
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

    public function testAdminCanConfirmOfficialQualification(): void
    {
        $competition = $this->persistMadFitnessFestival();
        $admin = (new User('Admin@example.com'))->setPassword('test-password')->setRoles(['ROLE_ADMIN']);
        $this->getEntityManager()->persist($admin);
        $this->getEntityManager()->flush();

        $command = $this->getService(SetOfficialCompetitionQualificationCommand::class);
        self::assertInstanceOf(SetOfficialCompetitionQualificationCommand::class, $command);

        $tester = new CommandTester($command);

        self::assertSame(Command::SUCCESS, $tester->execute([
            'action' => 'confirm',
            '--source' => 'competition_corner',
            '--external-id' => 'mad-2026',
            '--admin-email' => 'admin@EXAMPLE.com',
            '--notes' => 'Validated from the official CrossFit Games season announcement.',
        ]));
        $this->getEntityManager()->clear();

        $qualification = $this->findQualification($competition);
        self::assertNotNull($qualification);
        self::assertSame(CompetitionOfficialQualification::STATUS_CONFIRMED, $qualification->getStatus());
        self::assertSame(CompetitionOfficialQualification::SOURCE_ADMIN, $qualification->getSource());
        self::assertSame('Admin@example.com', $qualification->getConfirmedBy()?->getEmail());
        self::assertNotNull($qualification->getConfirmedAt());
        self::assertSame('Validated from the official CrossFit Games season announcement.', $qualification->getNotes());
    }

    public function testAdminCanDismissExistingOfficialQualificationSuggestion(): void
    {
        $competition = $this->persistMadFitnessFestival();
        $qualification = (new CompetitionOfficialQualification($competition, 'crossfit_games', 'semifinals', 'elite'))
            ->setSeason(2026)
            ->setNotes('Auto-suggested.');
        $this->getEntityManager()->persist($qualification);
        $this->getEntityManager()->flush();

        $command = $this->getService(SetOfficialCompetitionQualificationCommand::class);
        self::assertInstanceOf(SetOfficialCompetitionQualificationCommand::class, $command);

        $tester = new CommandTester($command);

        self::assertSame(Command::SUCCESS, $tester->execute([
            'action' => 'dismiss',
            '--source' => 'competition_corner',
            '--external-id' => 'mad-2026',
        ]));
        $this->getEntityManager()->clear();

        $storedQualification = $this->findQualification($competition);
        self::assertNotNull($storedQualification);
        self::assertSame(CompetitionOfficialQualification::STATUS_DISMISSED, $storedQualification->getStatus());
        self::assertSame(CompetitionOfficialQualification::SOURCE_ADMIN, $storedQualification->getSource());
        self::assertNotNull($storedQualification->getDismissedAt());
    }

    public function testConfirmFailsWhenExplicitAdminEmailDoesNotExist(): void
    {
        $competition = $this->persistMadFitnessFestival();
        $command = $this->getService(SetOfficialCompetitionQualificationCommand::class);
        self::assertInstanceOf(SetOfficialCompetitionQualificationCommand::class, $command);

        $tester = new CommandTester($command);

        self::assertSame(Command::FAILURE, $tester->execute([
            'action' => 'confirm',
            '--source' => 'competition_corner',
            '--external-id' => 'mad-2026',
            '--admin-email' => 'missing@example.com',
        ]));

        self::assertStringContainsString('was not found', $tester->getDisplay());
        self::assertNull($this->findQualification($competition));
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
