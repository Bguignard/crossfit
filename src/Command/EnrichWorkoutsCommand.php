<?php

namespace App\Command;

use App\Entity\Workout\Implement;
use App\Entity\Workout\Movement;
use App\Entity\Workout\Workout;
use App\Services\Workout\Enrichment\WorkoutEnrichmentMatcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:workouts:enrich',
    description: 'Propose or apply deterministic movement and implement links for monolithic workouts.'
)]
final class EnrichWorkoutsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WorkoutEnrichmentMatcher $matcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Persist deterministic links.')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Restrict enrichment to one workout name.')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum number of workouts to scan.')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Scan all workouts, including already enriched ones.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $apply = (bool) $input->getOption('apply');
        $name = $input->getOption('name');
        $limit = $input->getOption('limit') !== null ? max(1, (int) $input->getOption('limit')) : null;
        $scanAll = (bool) $input->getOption('all');

        $movements = $this->entityManager->getRepository(Movement::class)->findAll();
        $implements = $this->entityManager->getRepository(Implement::class)->findAll();
        $workouts = $this->getWorkouts(is_string($name) ? $name : null, $limit);

        $rows = [];
        $scanned = 0;
        $changed = 0;
        $needsReview = 0;

        foreach ($workouts as $workout) {
            if (!$scanAll && $workout->getMovements()->count() > 0 && $workout->getImplements()->count() > 0) {
                continue;
            }

            ++$scanned;
            $match = $this->matcher->match($workout, $movements, $implements);
            $newMovements = array_values(array_filter(
                $match->movements,
                static fn (Movement $movement): bool => !$workout->getMovements()->contains($movement)
            ));
            $newImplements = array_values(array_filter(
                $match->implements,
                static fn (Implement $implement): bool => !$workout->getImplements()->contains($implement)
            ));

            if ($match->ambiguousTerms !== []) {
                ++$needsReview;
            }

            if ($newMovements !== [] || $newImplements !== []) {
                ++$changed;
                if ($apply) {
                    foreach ($newMovements as $movement) {
                        $workout->addMovement($movement);
                    }
                    foreach ($newImplements as $implement) {
                        $workout->addImplement($implement);
                    }
                }
            }

            $rows[] = [
                $workout->getName(),
                $newMovements === [] ? '-' : implode(', ', array_map(static fn (Movement $movement): string => (string) $movement->getName(), $newMovements)),
                $newImplements === [] ? '-' : implode(', ', array_map(static fn (Implement $implement): string => $implement->getName(), $newImplements)),
                $match->ambiguousTerms === [] ? '-' : implode(', ', $match->ambiguousTerms),
            ];
        }

        if ($apply) {
            $this->entityManager->flush();
        }

        $io->title($apply ? 'Workout enrichment applied' : 'Workout enrichment dry-run');
        $io->definitionList(
            ['scanned' => $scanned],
            ['with_new_links' => $changed],
            ['needs_review' => $needsReview],
        );

        if ($rows !== []) {
            $table = new Table($output);
            $table
                ->setHeaders(['Workout', 'New movements', 'New implements', 'Needs review'])
                ->setRows($rows);
            $table->render();
        }

        if (!$apply) {
            $io->note('Dry-run only. Re-run with --apply to persist deterministic links.');
        }

        return Command::SUCCESS;
    }

    /**
     * @return list<Workout>
     */
    private function getWorkouts(?string $name, ?int $limit): array
    {
        $repository = $this->entityManager->getRepository(Workout::class);
        if ($name !== null) {
            return $repository->findBy(['name' => $name], ['name' => 'ASC'], $limit);
        }

        return $repository->findBy([], ['name' => 'ASC'], $limit);
    }
}
