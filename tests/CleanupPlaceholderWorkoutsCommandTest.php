<?php

namespace App\Tests;

use App\Command\CleanupPlaceholderWorkoutsCommand;
use App\Entity\Competition\Competition;
use App\Entity\Competition\CompetitionEvent;
use App\Entity\Workout\Enum\WorkoutOriginNameEnum;
use App\Entity\Workout\Workout;
use App\Entity\Workout\WorkoutOrigin;
use App\Entity\Workout\WorkoutOriginName;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class CleanupPlaceholderWorkoutsCommandTest extends AbstractIntegrationTest
{
    private int $fixtureCounter = 0;

    public function testDryRunDoesNotRemovePlaceholderWorkout(): void
    {
        [$workout] = $this->persistPlaceholderWorkoutContext();
        $command = $this->getService(CleanupPlaceholderWorkoutsCommand::class);
        self::assertInstanceOf(CleanupPlaceholderWorkoutsCommand::class, $command);

        $tester = new CommandTester($command);

        self::assertSame(Command::SUCCESS, $tester->execute(['--source' => 'competition_corner']));
        $this->getEntityManager()->clear();

        self::assertNotNull($this->getRepository(Workout::class)->find($workout->getId()));
    }

    public function testWriteDetachesEventsAndRemovesPlaceholderWorkout(): void
    {
        [$workout, $event] = $this->persistPlaceholderWorkoutContext();
        $workoutId = $workout->getId();
        $eventId = $event->getId();
        $command = $this->getService(CleanupPlaceholderWorkoutsCommand::class);
        self::assertInstanceOf(CleanupPlaceholderWorkoutsCommand::class, $command);

        $tester = new CommandTester($command);

        self::assertSame(Command::SUCCESS, $tester->execute([
            '--source' => 'competition_corner',
            '--write' => true,
        ]));
        $this->getEntityManager()->clear();

        self::assertNull($this->getRepository(Workout::class)->find($workoutId));

        /** @var CompetitionEvent|null $storedEvent */
        $storedEvent = $this->getRepository(CompetitionEvent::class)->find($eventId);
        self::assertNotNull($storedEvent);
        self::assertNull($storedEvent->getWorkout());
    }

    public function testWriteDetachesEventsAndRemovesEventLabelPlaceholderWorkout(): void
    {
        [$workout, $event] = $this->persistPlaceholderWorkoutContext('Workout WOD 1', 'WOD 1', 'WOD 1');
        $workoutId = $workout->getId();
        $eventId = $event->getId();
        $command = $this->getService(CleanupPlaceholderWorkoutsCommand::class);
        self::assertInstanceOf(CleanupPlaceholderWorkoutsCommand::class, $command);

        $tester = new CommandTester($command);

        self::assertSame(Command::SUCCESS, $tester->execute([
            '--source' => 'competition_corner',
            '--write' => true,
        ]));
        $this->getEntityManager()->clear();

        self::assertNull($this->getRepository(Workout::class)->find($workoutId));

        /** @var CompetitionEvent|null $storedEvent */
        $storedEvent = $this->getRepository(CompetitionEvent::class)->find($eventId);
        self::assertNotNull($storedEvent);
        self::assertNull($storedEvent->getWorkout());
    }

    public function testLimitAppliesAfterPlaceholderFilteringAcrossBatches(): void
    {
        $entityManager = $this->getEntityManager();
        for ($index = 1; $index <= 3; ++$index) {
            $this->persistPlaceholderWorkoutContext(
                sprintf('Real Workout %d', $index),
                sprintf("For time:\n%d Thrusters\n%d Pull-ups", 20 + $index, 10 + $index),
                sprintf('Real Event %d', $index),
            );
        }

        [$placeholder] = $this->persistPlaceholderWorkoutContext('Workout WOD 4', 'WOD 4', 'WOD 4');
        $placeholderId = $placeholder->getId();
        $command = $this->getService(CleanupPlaceholderWorkoutsCommand::class);
        self::assertInstanceOf(CleanupPlaceholderWorkoutsCommand::class, $command);

        $tester = new CommandTester($command);

        self::assertSame(Command::SUCCESS, $tester->execute([
            '--source' => 'competition_corner',
            '--limit' => 1,
            '--batch-size' => 2,
            '--write' => true,
        ]));
        $entityManager->clear();

        self::assertNull($this->getRepository(Workout::class)->find($placeholderId));
    }

    /**
     * @return array{0: Workout, 1: CompetitionEvent}
     */
    private function persistPlaceholderWorkoutContext(string $workoutName = 'Workout 1', string $flow = '*', string $eventName = 'Workout 1'): array
    {
        $entityManager = $this->getEntityManager();
        ++$this->fixtureCounter;
        $workout = (new Workout(
            $workoutName,
            $flow,
            null,
            null,
            null,
            new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::OTHER), 2025),
        ))
            ->setSourceName('competition_corner')
            ->setExternalId(sprintf('marseille-workout-%d', $this->fixtureCounter));
        $competition = new Competition(
            sprintf('Marseille Throwdown 2025 #%d', $this->fixtureCounter),
            'competition_corner',
            sprintf('15984-%d', $this->fixtureCounter),
        );
        $event = (new CompetitionEvent(
            $competition,
            $eventName,
            'competition_corner',
            sprintf('15984-workout-%d', $this->fixtureCounter),
        ))
            ->setWorkout($workout);

        foreach ([$workout, $competition, $event] as $entity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();

        return [$workout, $event];
    }
}
