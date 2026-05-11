<?php

namespace App\Tests;

class WorkoutApiWorkflowTest extends AbstractIntegrationTest
{
    public function testFrontendCanListWorkoutCatalogFromApi(): void
    {
        $this->browser()->request('GET', '/api/workouts?itemsPerPage=1000');

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $workouts = $payload['member'] ?? $payload['hydra:member'] ?? [];
        $names = array_map(static fn (array $workout): ?string => $workout['name'] ?? null, $workouts);

        self::assertContains('Fran', $names);
        self::assertLessThanOrEqual(50, count($workouts));
    }

    public function testFrontendCanListPublicAthletesEvenWhenCatalogIsEmpty(): void
    {
        $this->browser()->request('GET', '/api/athletes');

        self::assertResponseIsSuccessful();

        $payload = json_decode($this->browser()->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $athletes = $payload['member'] ?? $payload['hydra:member'] ?? null;

        self::assertIsArray($athletes);
    }

    public function testPublicWorkoutCatalogIsReadOnly(): void
    {
        $this->browser()->request(
            'POST',
            '/api/workouts',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Should not be created'], JSON_THROW_ON_ERROR)
        );

        self::assertResponseStatusCodeSame(405);
    }

    public function testWorkoutGenerationIsNotPubliclyListedOrTriggeredByGet(): void
    {
        $this->browser()->request('GET', '/api/workout_generations');

        self::assertResponseStatusCodeSame(404);

        $this->browser()->request('GET', '/api/workout-generator/00000000-0000-0000-0000-000000000000');

        self::assertResponseStatusCodeSame(405);
    }
}
