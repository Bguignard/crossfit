<?php

namespace App\Tests;

use App\Entity\Workout\WorkoutPrescriptionStandard;
use App\Repository\Workout\WorkoutPrescriptionStandardRepository;

final class WorkoutPrescriptionStandardRepositoryTest extends AbstractIntegrationTest
{
    public function testFindForPromptDoesNotReturnOtherMovementsOnlyBecauseImplementMatches(): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist(new WorkoutPrescriptionStandard('repository_test', 'crossfit', 'Repository Test', 'women', 'Snatch', 'barbell', '38.00', 'kg', 1, 'matching snatch', null, 1));
        $entityManager->persist(new WorkoutPrescriptionStandard('repository_test', 'crossfit', 'Repository Test', 'women', 'Deadlift', 'barbell', '102.00', 'kg', 1, 'wrong movement', null, 1));
        $entityManager->persist(new WorkoutPrescriptionStandard('repository_test', 'crossfit', 'Repository Test', 'women', null, 'barbell', '30.00', 'kg', 1, 'generic barbell', null, 1));
        $entityManager->persist(new WorkoutPrescriptionStandard('repository_test', 'crossfit', 'Repository Test', 'women', null, null, '20.00', 'kg', 1, 'global', null, 1));
        $entityManager->flush();

        /** @var WorkoutPrescriptionStandardRepository $repository */
        $repository = $this->getRepository(WorkoutPrescriptionStandard::class);
        $standards = $repository->findForPrompt('Repository Test', ['Snatch'], ['barbell'], false, 10);

        $contextLabels = array_map(
            static fn (WorkoutPrescriptionStandard $standard): ?string => $standard->getContextLabel(),
            $standards,
        );

        self::assertContains('matching snatch', $contextLabels);
        self::assertContains('generic barbell', $contextLabels);
        self::assertContains('global', $contextLabels);
        self::assertNotContains('wrong movement', $contextLabels);
    }

    public function testFindForPromptPrioritizesExactMovementStandardsBeforeGenericImplementStandards(): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist(new WorkoutPrescriptionStandard('repository_test', 'crossfit', 'Repository Relevance', 'women', null, 'barbell', '30.00', 'kg', 1, 'generic barbell', null, 1));
        $entityManager->persist(new WorkoutPrescriptionStandard('repository_test', 'crossfit', 'Repository Relevance', 'women', 'Deadlift', 'barbell', '102.00', 'kg', 1, 'exact deadlift', null, 80));
        $entityManager->flush();

        /** @var WorkoutPrescriptionStandardRepository $repository */
        $repository = $this->getRepository(WorkoutPrescriptionStandard::class);
        $standards = $repository->findForPrompt('Repository Relevance', ['Deadlift'], ['barbell'], false, 1);

        self::assertCount(1, $standards);
        self::assertSame('exact deadlift', $standards[0]->getContextLabel());
    }
}
