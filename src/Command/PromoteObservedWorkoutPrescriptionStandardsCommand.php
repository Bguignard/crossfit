<?php

namespace App\Command;

use App\Entity\Workout\Workout;
use App\Entity\Workout\WorkoutPrescriptionStandard;
use App\Services\Workout\Prescription\WorkoutLoadCandidate;
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
    name: 'app:workouts:promote-observed-prescription-standards',
    description: 'Promote reliable imported workout load signals into observed prescription standards.'
)]
final class PromoteObservedWorkoutPrescriptionStandardsCommand extends Command
{
    private const OBSERVED_SOURCE_NAME = 'crossfit_games_observed';

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
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Restrict the scan to one source name.', 'crossfit_games')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum number of workouts to scan.', 200)
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format: table or json.', 'table')
            ->addOption('write', null, InputOption::VALUE_NONE, 'Persist promoted standards. Without this flag the command is a dry run.')
            ->addOption('show-duplicates', null, InputOption::VALUE_NONE, 'Include duplicate candidates in the report.')
            ->addOption('show-skipped', null, InputOption::VALUE_NONE, 'Include skipped candidates in the report.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $this->stringOption($input->getOption('name'));
        $source = $this->stringOption($input->getOption('source'));
        $limit = max(1, (int) $input->getOption('limit'));
        $format = $this->stringOption($input->getOption('format')) ?? 'table';
        $write = (bool) $input->getOption('write');
        $showDuplicates = (bool) $input->getOption('show-duplicates');
        $showSkipped = (bool) $input->getOption('show-skipped');

        if (!in_array($format, ['table', 'json'], true)) {
            $io->error('Invalid format. Use "table" or "json".');

            return Command::INVALID;
        }

        $rows = [];
        $stats = [
            'scanned' => 0,
            'promotable' => 0,
            'created' => 0,
            'existing' => 0,
            'duplicates' => 0,
            'skipped' => 0,
        ];
        $seenPayloadKeys = [];

        foreach ($this->workouts($name, $source, $limit) as $workout) {
            ++$stats['scanned'];
            $prescription = $this->inferer->infer($workout);
            foreach ($prescription->loadCandidates as $candidate) {
                $standardPayloads = $this->standardPayloads($workout, $candidate, $prescription->levelHints);
                if ($standardPayloads === []) {
                    ++$stats['skipped'];
                    if ($showSkipped) {
                        $rows[] = $this->row($workout, $candidate, 'skipped', null);
                    }
                    continue;
                }

                foreach ($standardPayloads as $standardPayload) {
                    ++$stats['promotable'];
                    $payloadKey = $this->payloadKey($standardPayload);
                    if (isset($seenPayloadKeys[$payloadKey])) {
                        ++$stats['duplicates'];
                        if ($showDuplicates) {
                            $rows[] = $this->row($workout, $candidate, 'duplicate', $standardPayload);
                        }
                        continue;
                    }
                    $seenPayloadKeys[$payloadKey] = true;

                    $existing = $this->existingStandard($standardPayload);
                    if ($existing instanceof WorkoutPrescriptionStandard) {
                        ++$stats['existing'];
                        $rows[] = $this->row($workout, $candidate, 'existing', $standardPayload);
                        continue;
                    }

                    if ($write) {
                        $this->entityManager->persist(new WorkoutPrescriptionStandard(...$standardPayload));
                        ++$stats['created'];
                        $rows[] = $this->row($workout, $candidate, 'created', $standardPayload);
                        continue;
                    }

                    $rows[] = $this->row($workout, $candidate, 'would_create', $standardPayload);
                }
            }
        }

        if ($write) {
            $this->entityManager->flush();
        }

        if ($format === 'json') {
            $output->writeln(json_encode([
                'dryRun' => !$write,
                'stats' => $stats,
                'standards' => $rows,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE));

            return Command::SUCCESS;
        }

        $io->title($write ? 'Observed prescription standards promotion' : 'Observed prescription standards dry run');
        $io->definitionList(
            ['scanned' => $stats['scanned']],
            ['promotable' => $stats['promotable']],
            ['created' => $stats['created']],
            ['existing' => $stats['existing']],
            ['duplicates' => $stats['duplicates']],
            ['skipped' => $stats['skipped']],
        );

        if ($rows !== []) {
            $table = new Table($output);
            $table
                ->setHeaders(['Status', 'Workout', 'Division', 'Movement', 'Implement', 'Load', 'Level', 'Candidate'])
                ->setRows(array_map(static fn (array $row): array => [
                    $row['status'],
                    $row['workoutName'],
                    $row['division'] ?? '-',
                    $row['movementName'] ?? '-',
                    $row['implementName'] ?? '-',
                    $row['quantity'] === null ? '-' : $row['quantity'].' '.$row['unit'],
                    $row['levelName'] ?? '-',
                    $row['candidateLabel'],
                ], $rows));
            $table->render();
        } else {
            $io->note('No candidate matched the current filters.');
        }

        $io->note($write ? 'Promotion complete.' : 'Dry run only. Re-run with --write to persist.');

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

    /**
     * @param list<string> $levelHints
     *
     * @return list<array{
     *     sourceName: string,
     *     sport: string,
     *     levelName: string|null,
     *     division: string,
     *     movementName: string|null,
     *     implementName: string|null,
     *     quantity: string,
     *     unit: string,
     *     quantityMultiplier: int,
     *     contextLabel: string|null,
     *     notes: string|null,
     *     priority: int
     * }>
     */
    private function standardPayloads(Workout $workout, WorkoutLoadCandidate $candidate, array $levelHints): array
    {
        if ($candidate->kind !== 'conversion') {
            return [];
        }

        if ($candidate->equipmentHint === 'unknown') {
            return [];
        }

        $contextHints = $candidate->contextHints();
        $audiences = array_values(array_unique(array_filter(array_map(
            $this->normalizedAudience(...),
            $contextHints['audiences'] ?? [],
        ))));
        if (count($audiences) !== 1) {
            return [];
        }

        $movementName = count($contextHints['movements'] ?? []) === 1 ? $contextHints['movements'][0] : null;
        if ($movementName === null || !$this->isCompatibleMovement($candidate->equipmentHint, $movementName)) {
            return [];
        }

        $mention = $this->preferredMention($candidate);
        if (!$mention instanceof WorkoutLoadMention || $mention->values === []) {
            return [];
        }

        $values = array_values(array_unique($mention->values));
        $positionLabel = count($contextHints['positions'] ?? []) === 1 ? $contextHints['positions'][0] : null;
        $payloads = [];

        foreach ($values as $value) {
            $payloads[] = [
                'sourceName' => self::OBSERVED_SOURCE_NAME,
                'sport' => 'crossfit',
                'levelName' => $this->levelName($levelHints),
                'division' => $audiences[0],
                'movementName' => $movementName,
                'implementName' => $candidate->equipmentHint,
                'quantity' => $this->quantityString($value),
                'unit' => $mention->unit,
                'quantityMultiplier' => 1,
                'contextLabel' => $positionLabel,
                'notes' => $this->notes($workout, $candidate),
                'priority' => 80,
            ];
        }

        return $payloads;
    }

    private function preferredMention(WorkoutLoadCandidate $candidate): ?WorkoutLoadMention
    {
        if ($candidate->kind === 'conversion') {
            foreach ($candidate->mentions as $mention) {
                if ($mention->unit === 'kg') {
                    return $mention;
                }
            }
        }

        return $candidate->mentions[0] ?? null;
    }

    /**
     * @param list<string> $levelHints
     */
    private function levelName(array $levelHints): ?string
    {
        foreach (['elite', 'rx', 'scaled', 'intermediate', 'beginner', 'masters', 'teen'] as $level) {
            if (in_array($level, $levelHints, true)) {
                return $level === 'rx' ? 'RX' : ucfirst($level);
            }
        }

        return null;
    }

    private function isCompatibleMovement(string $equipmentHint, string $movementName): bool
    {
        $compatibleMovements = [
            'barbell' => [
                'Clean',
                'Clean and Jerk',
                'Deadlift',
                'Front Squat',
                'Hang Power Clean',
                'Overhead Squat',
                'Snatch',
                'Squat Clean',
                'Thruster',
            ],
            'dumbbell' => [
                'Clean',
                'Farmer Carry',
                'Snatch',
                'Walking Lunge',
            ],
            'kettlebell' => [
                'Farmer Carry',
                'Walking Lunge',
            ],
            'medicine ball' => [
                'Wall Ball Shot',
            ],
            'sled' => [
                'Sled Pull',
                'Sled Push',
            ],
            'sandbag' => [
                'Walking Lunge',
            ],
        ];

        return in_array($movementName, $compatibleMovements[$equipmentHint] ?? [], true);
    }

    private function normalizedAudience(string $audience): ?string
    {
        return match ($audience) {
            'women', 'ff' => 'women',
            'men', 'mm' => 'men',
            default => null,
        };
    }

    /**
     * @param array{
     *     sourceName: string,
     *     sport: string,
     *     levelName: string|null,
     *     division: string,
     *     movementName: string|null,
     *     implementName: string|null,
     *     quantity: string,
     *     unit: string,
     *     quantityMultiplier: int,
     *     contextLabel: string|null,
     *     notes: string|null,
     *     priority: int
     * } $payload
     */
    private function existingStandard(array $payload): ?WorkoutPrescriptionStandard
    {
        return $this->entityManager->getRepository(WorkoutPrescriptionStandard::class)->findOneBy([
            'sourceName' => $payload['sourceName'],
            'sport' => $payload['sport'],
            'levelName' => $payload['levelName'],
            'division' => $payload['division'],
            'movementName' => $payload['movementName'],
            'implementName' => $payload['implementName'],
            'quantity' => $payload['quantity'],
            'unit' => $payload['unit'],
            'quantityMultiplier' => $payload['quantityMultiplier'],
            'contextLabel' => $payload['contextLabel'],
        ]);
    }

    /**
     * @param array<string, mixed>|null $payload
     *
     * @return array<string, mixed>
     */
    private function row(
        Workout $workout,
        WorkoutLoadCandidate $candidate,
        string $status,
        ?array $payload,
    ): array {
        return [
            'status' => $status,
            'workoutName' => $workout->getName(),
            'sourceName' => $workout->getSourceName(),
            'externalId' => $workout->getExternalId(),
            'division' => $payload['division'] ?? null,
            'movementName' => $payload['movementName'] ?? null,
            'implementName' => $payload['implementName'] ?? null,
            'quantity' => $payload['quantity'] ?? null,
            'unit' => $payload['unit'] ?? null,
            'levelName' => $payload['levelName'] ?? null,
            'contextLabel' => $payload['contextLabel'] ?? null,
            'candidateLabel' => $candidate->label(),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function payloadKey(array $payload): string
    {
        return implode('|', array_map(
            static fn (mixed $value): string => $value === null ? '' : (string) $value,
            [
                $payload['sourceName'],
                $payload['sport'],
                $payload['levelName'],
                $payload['division'],
                $payload['movementName'],
                $payload['implementName'],
                $payload['quantity'],
                $payload['unit'],
                $payload['quantityMultiplier'],
                $payload['contextLabel'],
            ],
        ));
    }

    private function notes(Workout $workout, WorkoutLoadCandidate $candidate): string
    {
        return substr(implode(' | ', array_filter([
            $workout->getName(),
            $workout->getExternalId(),
            $candidate->label(),
        ])), 0, 255);
    }

    private function quantityString(float $quantity): string
    {
        return number_format($quantity, 2, '.', '');
    }

    private function stringOption(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}
