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

    /**
     * @return array{0: Workout, 1: CompetitionEvent}
     */
    private function persistPlaceholderWorkoutContext(): array
    {
        $entityManager = $this->getEntityManager();
        $workout = (new Workout(
            'Workout 1',
            '*',
            null,
            null,
            null,
            new WorkoutOrigin(new WorkoutOriginName(WorkoutOriginNameEnum::OTHER), 2025),
        ))
            ->setSourceName('competition_corner')
            ->setExternalId('marseille-workout-1');
        $competition = new Competition('Marseille Throwdown 2025', 'competition_corner', '15984');
        $event = (new CompetitionEvent($competition, 'Workout 1', 'competition_corner', '15984-workout-1'))
            ->setWorkout($workout);

        foreach ([$workout, $competition, $event] as $entity) {
            $entityManager->persist($entity);
        }
        $entityManager->flush();

        return [$workout, $event];
    }
}
