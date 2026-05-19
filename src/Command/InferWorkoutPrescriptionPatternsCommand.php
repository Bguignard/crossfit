<?php

namespace App\Command;

use App\Entity\Workout\Workout;
use App\Services\Workout\Prescription\WorkoutLoadMention;
use App\Services\Workout\Prescription\WorkoutPrescriptionPatternInferer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:workouts:infer-prescription-patterns',
    description: 'Inspect imported workouts and report load/level signals that could feed generation scaling.'
)]
final class InferWorkoutPrescriptionPatternsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WorkoutPrescriptionPatternInferer $inferer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Restrict the scan to one workout name.')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Restrict the scan to one source name.')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum number of workouts to scan.', 50)
            ->addOption('with-signal-only', null, InputOption::VALUE_NONE, 'Only display workouts with detected loads or level hints.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = max(1, (int) $input->getOption('limit'));
        $name = $this->stringOption($input->getOption('name'));
        $source = $this->stringOption($input->getOption('source'));
        $withSignalOnly = (bool) $input->getOption('with-signal-only');

        $workouts = $this->workouts($name, $source, $limit);
        $rows = [];
        $scanned = 0;
        $withLoads = 0;
        $withLevelHints = 0;

        foreach ($workouts as $workout) {
            ++$scanned;
            $prescription = $this->inferer->infer($workout);

            if ($prescription->loads !== []) {
                ++$withLoads;
            }
            if ($prescription->levelHints !== []) {
                ++$withLevelHints;
            }
            if ($withSignalOnly && !$prescription->hasActionableSignal()) {
                continue;
            }

            $rows[] = [
                $workout->getName() ?? '-',
                $workout->getSourceName() ?? '-',
                $workout->getExternalId() ?? '-',
                $this->listLabel($prescription->levelHints),
                $this->listLabel($prescription->divisionHints, 4),
                $this->listLabel($prescription->movementNames, 4),
                $this->listLabel(array_map(static fn (WorkoutLoadMention $load): string => $load->label(), $prescription->loads), 5),
                $this->flowPreview($workout),
            ];
        }

        $io->title('Workout prescription pattern report');
        $io->definitionList(
            ['scanned' => $scanned],
            ['with_loads' => $withLoads],
            ['with_level_hints' => $withLevelHints],
        );

        if ($rows !== []) {
            $table = new Table($output);
            $table
                ->setHeaders(['Workout', 'Source', 'External ID', 'Levels', 'Divisions', 'Movements', 'Loads', 'Flow preview'])
                ->setRows($rows);
            $table->render();
        } else {
            $io->note('No workout matched the current display filters.');
        }

        $io->note('Report only. No data was written.');

        return Command::SUCCESS;
    }

    /**
     * @return list<Workout>
     */
    private function workouts(?string $name, ?string $source, int $limit): array
    {
        $queryBuilder = $this->entityManager->getRepository(Workout::class)
            ->createQueryBuilder('workout')
            ->orderBy('workout.createdAt', 'DESC')
            ->setMaxResults($limit);

        if ($name !== null) {
            $queryBuilder
                ->andWhere('LOWER(workout.name) LIKE LOWER(:name)')
                ->setParameter('name', '%'.$name.'%');
        }

        if ($source !== null) {
            $queryBuilder
                ->andWhere('workout.sourceName = :source')
                ->setParameter('source', $source);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    private function stringOption(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }

    /**
     * @param list<string> $values
     */
    private function listLabel(array $values, int $max = 3): string
    {
        if ($values === []) {
            return '-';
        }

        $shown = array_slice($values, 0, $max);
        $extra = count($values) - count($shown);

        return implode(', ', $shown).($extra > 0 ? sprintf(' +%d', $extra) : '');
    }

    private function flowPreview(Workout $workout): string
    {
        $preview = trim((string) preg_replace('/\s+/', ' ', $workout->getFlow()));

        if (strlen($preview) <= 100) {
            return $preview;
        }

        return substr($preview, 0, 97).'...';
    }
}
