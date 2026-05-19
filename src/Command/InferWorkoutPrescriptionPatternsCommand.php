<?php

namespace App\Command;

use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\Workout;
use App\Services\Workout\Enrichment\WorkoutEnrichmentMatcher;
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
        private readonly WorkoutEnrichmentMatcher $enrichmentMatcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Restrict the scan to one workout name.')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Restrict the scan to one source name.')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum number of workouts to scan.', 50)
            ->addOption('with-signal-only', null, InputOption::VALUE_NONE, 'Only display workouts with detected loads or level hints.')
            ->addOption('show-duplicates', null, InputOption::VALUE_NONE, 'Display every imported variant instead of one row per unique source/name/flow.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = max(1, (int) $input->getOption('limit'));
        $name = $this->stringOption($input->getOption('name'));
        $source = $this->stringOption($input->getOption('source'));
        $withSignalOnly = (bool) $input->getOption('with-signal-only');
        $dedupe = !(bool) $input->getOption('show-duplicates');
        $movements = $this->entityManager->getRepository(Movement::class)->findAll();
        $implements = $this->entityManager->getRepository(Implement::class)->findAll();

        $workouts = $this->workouts($name, $source, $dedupe ? $limit * 10 : $limit);
        $rows = [];
        $seenWorkoutKeys = [];
        $scanned = 0;
        $displayed = 0;
        $deduped = 0;
        $withLoads = 0;
        $withLevelHints = 0;

        foreach ($workouts as $workout) {
            ++$scanned;
            if ($dedupe) {
                $key = $this->workoutKey($workout);
                if (isset($seenWorkoutKeys[$key])) {
                    ++$deduped;
                    continue;
                }
                $seenWorkoutKeys[$key] = true;
            }

            $prescription = $this->inferer->infer($workout);
            $enrichmentMatch = $this->enrichmentMatcher->match($workout, $movements, $implements);
            $movementNames = $prescription->movementNames !== []
                ? $prescription->movementNames
                : $this->movementNames($enrichmentMatch->movements);
            $implementNames = $prescription->implementNames !== []
                ? $prescription->implementNames
                : $this->implementNames($enrichmentMatch->implements);

            if ($prescription->loads !== []) {
                ++$withLoads;
            }
            if ($prescription->levelHints !== []) {
                ++$withLevelHints;
            }
            if ($withSignalOnly && !$prescription->hasActionableSignal()) {
                continue;
            }
            if ($displayed >= $limit) {
                break;
            }
            ++$displayed;

            $rows[] = [
                $workout->getName() ?? '-',
                $workout->getSourceName() ?? '-',
                $workout->getExternalId() ?? '-',
                $this->listLabel($prescription->levelHints),
                $this->listLabel($prescription->divisionHints, 4),
                $this->listLabel($movementNames, 4),
                $this->listLabel($implementNames, 3),
                $this->listLabel(array_map(static fn (WorkoutLoadMention $load): string => $load->label(), $prescription->loads), 5),
                $this->flowPreview($workout),
            ];
        }

        $io->title('Workout prescription pattern report');
        $io->definitionList(
            ['scanned' => $scanned],
            ['displayed' => $displayed],
            ['deduped' => $deduped],
            ['with_loads' => $withLoads],
            ['with_level_hints' => $withLevelHints],
        );

        if ($rows !== []) {
            $table = new Table($output);
            $table
                ->setHeaders(['Workout', 'Source', 'External ID', 'Levels', 'Divisions', 'Movements', 'Implements', 'Loads', 'Flow preview'])
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

    private function workoutKey(Workout $workout): string
    {
        return implode('|', [
            $workout->getSourceName() ?? '',
            mb_strtolower((string) $workout->getName()),
            sha1(trim((string) preg_replace('/\s+/', ' ', $workout->getFlow()))),
        ]);
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

    /**
     * @param list<Movement> $movements
     *
     * @return list<string>
     */
    private function movementNames(array $movements): array
    {
        $names = array_filter(array_map(
            static fn (Movement $movement): ?string => $movement->getName(),
            $movements,
        ));
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return array_values($names);
    }

    /**
     * @param list<Implement> $implements
     *
     * @return list<string>
     */
    private function implementNames(array $implements): array
    {
        $names = array_map(
            static fn (Implement $implement): string => $implement->getName(),
            $implements,
        );
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return array_values($names);
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
