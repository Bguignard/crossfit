<?php

namespace App\Command;

use App\Entity\Competition\CompetitionEvent;
use App\Entity\Workout\Workout;
use App\Services\Workout\WorkoutPlaceholderFlowDetector;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:workouts:cleanup-placeholder-flows',
    description: 'Detach and remove imported workouts whose flow is only a placeholder.',
)]
final class CleanupPlaceholderWorkoutsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WorkoutPlaceholderFlowDetector $placeholderFlowDetector,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum placeholder workouts to inspect.', 500)
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Only clean workouts from this source.')
            ->addOption('write', null, InputOption::VALUE_NONE, 'Persist the cleanup.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = max(1, (int) $input->getOption('limit'));
        $source = $this->stringOrNull($input->getOption('source'));
        $write = (bool) $input->getOption('write');
        $workouts = $this->placeholderWorkouts($limit, $source);
        $detachedEvents = 0;
        $workoutRows = [];

        foreach ($workouts as $workout) {
            $events = $this->eventsForWorkout($workout);
            $detachedEvents += count($events);
            $workoutRows[] = [
                $workout->getName(),
                $workout->getSourceName(),
                $workout->getExternalId(),
                $workout->getFlow(),
                count($events),
            ];
            if (!$write) {
                continue;
            }

            foreach ($events as $event) {
                $event->setWorkout(null);
            }
            $this->entityManager->remove($workout);
        }

        if ($write) {
            $this->entityManager->flush();
        }

        $io->title($write ? 'Placeholder workout cleanup' : 'Placeholder workout cleanup dry run');
        $io->table(
            [$write ? 'removed_workouts' : 'would_remove', $write ? 'detached_events' : 'would_detach_events'],
            [[count($workouts), $detachedEvents]],
        );

        if ($workouts !== []) {
            $io->table(
                ['Workout', 'Source', 'External ID', 'Flow', 'Events'],
                array_slice($workoutRows, 0, 20),
            );
        }

        if (!$write) {
            $io->note('Dry run only. Re-run with --write to persist.');
        }

        return Command::SUCCESS;
    }

    /**
     * @return list<Workout>
     */
    private function placeholderWorkouts(int $limit, ?string $source): array
    {
        $queryBuilder = $this->entityManager->getRepository(Workout::class)->createQueryBuilder('workout')
            ->andWhere('workout.sourceName IS NOT NULL')
            ->setMaxResults($limit)
            ->orderBy('workout.createdAt', 'ASC');

        if ($source !== null) {
            $queryBuilder
                ->andWhere('workout.sourceName = :source')
                ->setParameter('source', $source);
        }

        /** @var list<Workout> $workouts */
        $workouts = $queryBuilder->getQuery()->getResult();

        return array_values(array_filter($workouts, function (Workout $workout): bool {
            foreach ($this->eventsForWorkout($workout) as $event) {
                if ($this->placeholderFlowDetector->displayableFlow($workout, $event->getName()) === null) {
                    return true;
                }
            }

            return $this->placeholderFlowDetector->displayableFlow($workout) === null;
        }));
    }

    /**
     * @return list<CompetitionEvent>
     */
    private function eventsForWorkout(Workout $workout): array
    {
        /** @var list<CompetitionEvent> $events */
        $events = $this->entityManager->getRepository(CompetitionEvent::class)->findBy(['workout' => $workout]);

        return $events;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
