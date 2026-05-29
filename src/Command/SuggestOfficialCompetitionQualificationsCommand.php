<?php

namespace App\Command;

use App\Entity\Competition\Competition;
use App\Entity\Competition\CompetitionOfficialQualification;
use App\Services\Competition\CompetitionOfficialQualificationSuggester;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:competitions:suggest-official-qualifications',
    description: 'Suggest official CrossFit Games circuit qualifications for imported competitions.',
)]
final class SuggestOfficialCompetitionQualificationsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CompetitionOfficialQualificationSuggester $suggester,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum number of competitions to inspect.', '5000')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Only inspect one competition source.')
            ->addOption('write', null, InputOption::VALUE_NONE, 'Persist suggested qualifications.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = max(1, (int) $input->getOption('limit'));
        $source = $this->stringOrNull($input->getOption('source'));
        $write = (bool) $input->getOption('write');

        $queryBuilder = $this->entityManager->getRepository(Competition::class)
            ->createQueryBuilder('competition')
            ->orderBy('competition.season', 'DESC')
            ->addOrderBy('competition.startsAt', 'DESC')
            ->addOrderBy('competition.name', 'ASC')
            ->setMaxResults($limit);

        if ($source !== null) {
            $queryBuilder
                ->andWhere('competition.sourceName = :source')
                ->setParameter('source', $source);
        }

        /** @var list<Competition> $competitions */
        $competitions = $queryBuilder->getQuery()->getResult();

        $stats = [
            'scanned' => count($competitions),
            'suggested' => 0,
            'existing' => 0,
            'created' => 0,
        ];
        $rows = [];

        foreach ($competitions as $competition) {
            foreach ($this->suggester->suggest($competition) as $suggestion) {
                ++$stats['suggested'];

                $qualification = $this->findQualification(
                    $competition,
                    $suggestion['circuit'],
                    $suggestion['stage'],
                    $suggestion['divisionPattern'],
                );

                if ($qualification !== null) {
                    ++$stats['existing'];
                    $status = $qualification->getStatus();
                } else {
                    $status = $write ? 'created' : 'would_create';
                    if ($write) {
                        $qualification = (new CompetitionOfficialQualification(
                            $competition,
                            $suggestion['circuit'],
                            $suggestion['stage'],
                            $suggestion['divisionPattern'],
                        ))
                            ->setSeason($competition->getSeason())
                            ->setNotes($suggestion['notes']);
                        $this->entityManager->persist($qualification);
                        ++$stats['created'];
                    }
                }

                $rows[] = [
                    $status,
                    $competition->getName(),
                    $competition->getSeason() ?? '-',
                    $competition->getSourceName(),
                    $suggestion['circuit'],
                    $suggestion['stage'],
                    $suggestion['divisionPattern'],
                ];
            }
        }

        if ($write) {
            $this->entityManager->flush();
        }

        $io->title('Official competition qualification suggestions');
        $io->table(array_keys($stats), [array_values($stats)]);

        if ($rows !== []) {
            (new Table($output))
                ->setHeaders(['Status', 'Competition', 'Season', 'Source', 'Circuit', 'Stage', 'Divisions'])
                ->setRows(array_slice($rows, 0, 30))
                ->render();
        }

        if (!$write) {
            $io->note('Dry run only. Re-run with --write to persist suggestions.');
        }

        return Command::SUCCESS;
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

    private function stringOrNull(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
