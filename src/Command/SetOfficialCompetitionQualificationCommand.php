<?php

namespace App\Command;

use App\Entity\Competition\Competition;
use App\Entity\Competition\CompetitionOfficialQualification;
use App\Entity\Security\User;
use App\Services\Competition\CompetitionOfficialQualificationSuggester;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:competitions:set-official-qualification',
    description: 'Confirm, dismiss or add an official CrossFit Games circuit qualification for a competition.',
)]
final class SetOfficialCompetitionQualificationCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::REQUIRED, 'One of confirm, dismiss or suggest.')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Competition source name, for example competition_corner.')
            ->addOption('external-id', null, InputOption::VALUE_REQUIRED, 'Competition external id in the selected source.')
            ->addOption('circuit', null, InputOption::VALUE_REQUIRED, 'Official circuit.', CompetitionOfficialQualificationSuggester::CIRCUIT_CROSSFIT_GAMES)
            ->addOption('stage', null, InputOption::VALUE_REQUIRED, 'Official circuit stage.', CompetitionOfficialQualificationSuggester::STAGE_SEMIFINALS)
            ->addOption('division-pattern', null, InputOption::VALUE_REQUIRED, 'Division pattern covered by the qualification.', CompetitionOfficialQualificationSuggester::DIVISION_PATTERN_ELITE)
            ->addOption('season', null, InputOption::VALUE_REQUIRED, 'Override the qualification season.')
            ->addOption('admin-email', null, InputOption::VALUE_REQUIRED, 'Admin user email to store as confirmer.')
            ->addOption('notes', null, InputOption::VALUE_REQUIRED, 'Optional admin note.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = $this->requiredChoice($input->getArgument('action'), ['confirm', 'dismiss', 'suggest']);
        $source = $this->requiredString($input->getOption('source'));
        $externalId = $this->requiredString($input->getOption('external-id'));
        $circuit = $this->requiredString($input->getOption('circuit'));
        $stage = $this->requiredString($input->getOption('stage'));
        $divisionPattern = $this->requiredString($input->getOption('division-pattern'));
        $notes = $this->stringOrNull($input->getOption('notes'));

        if ($action === null || $source === null || $externalId === null || $circuit === null || $stage === null || $divisionPattern === null) {
            $io->error('Missing or invalid official qualification arguments.');

            return Command::INVALID;
        }

        /** @var Competition|null $competition */
        $competition = $this->entityManager->getRepository(Competition::class)->findOneBy([
            'sourceName' => $source,
            'externalId' => $externalId,
        ]);

        if (!$competition instanceof Competition) {
            $io->error(sprintf('Competition not found for %s/%s.', $source, $externalId));

            return Command::FAILURE;
        }

        $adminUser = $this->adminUser($input);
        if ($adminUser === false) {
            $io->error(sprintf('Admin user "%s" was not found.', (string) $input->getOption('admin-email')));

            return Command::FAILURE;
        }

        $qualification = $this->findQualification($competition, $circuit, $stage, $divisionPattern);
        if (!$qualification instanceof CompetitionOfficialQualification) {
            $qualification = (new CompetitionOfficialQualification($competition, $circuit, $stage, $divisionPattern))
                ->setSeason($this->season($input, $competition));
            $this->entityManager->persist($qualification);
        } elseif ($this->stringOrNull($input->getOption('season')) !== null) {
            $qualification->setSeason($this->season($input, $competition));
        }

        if ($notes !== null) {
            $qualification->setNotes($notes);
        }

        match ($action) {
            'confirm' => $qualification->confirm($adminUser),
            'dismiss' => $qualification->dismiss(),
            'suggest' => $qualification->suggest(CompetitionOfficialQualification::SOURCE_ADMIN),
            default => throw new \LogicException(sprintf('Unsupported action "%s".', $action)),
        };

        $this->entityManager->flush();

        $io->success(sprintf(
            '%s qualification %s/%s/%s for "%s".',
            ucfirst($qualification->getStatus()),
            $qualification->getCircuit(),
            $qualification->getStage(),
            $qualification->getDivisionPattern(),
            $competition->getName(),
        ));

        return Command::SUCCESS;
    }

    /**
     * @param list<string> $allowed
     */
    private function requiredChoice(mixed $value, array $allowed): ?string
    {
        $value = $this->stringOrNull($value);
        if ($value === null || !in_array($value, $allowed, true)) {
            return null;
        }

        return $value;
    }

    private function requiredString(mixed $value): ?string
    {
        $value = $this->stringOrNull($value);
        if ($value === null) {
            return null;
        }

        return $value;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function season(InputInterface $input, Competition $competition): ?int
    {
        $season = $this->stringOrNull($input->getOption('season'));
        if ($season === null) {
            return $competition->getSeason();
        }

        return (int) $season;
    }

    private function adminUser(InputInterface $input): User|false|null
    {
        $email = $this->stringOrNull($input->getOption('admin-email'));
        if ($email === null) {
            return null;
        }

        /** @var User|null $user */
        $user = $this->entityManager
            ->createQueryBuilder()
            ->select('user')
            ->from(User::class, 'user')
            ->where('LOWER(user.email) = :email')
            ->setParameter('email', mb_strtolower($email))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $user ?? false;
    }

    private function findQualification(
        Competition $competition,
        string $circuit,
        string $stage,
        string $divisionPattern,
    ): ?CompetitionOfficialQualification {
        /** @var CompetitionOfficialQualification|null $qualification */
        $qualification = $this->entityManager->getRepository(CompetitionOfficialQualification::class)->findOneBy([
            'competition' => $competition,
            'circuit' => $circuit,
            'stage' => $stage,
            'divisionPattern' => $divisionPattern,
        ]);

        return $qualification;
    }
}
