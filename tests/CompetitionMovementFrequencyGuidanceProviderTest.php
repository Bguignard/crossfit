<?php

namespace App\Tests;

use App\Entity\Workout\Enum\MovementDifficultyEnum;
use App\Entity\Workout\Enum\MovementTypeEnum;
use App\Entity\Workout\Movement;
use App\Entity\Workout\MovementDifficulty;
use App\Entity\Workout\MovementType;
use App\Entity\WorkoutGeneration\WorkoutGeneration;
use App\Services\Workout\CompetitionMovementFrequencyGuidanceProvider;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 */
class CompetitionMovementFrequencyGuidanceProviderTest extends TestCase
{
    public function testAvailableCompetitionGuidanceDataIsFilteredByAllowedMovements(): void
    {
        $provider = new CompetitionMovementFrequencyGuidanceProvider();
        $movements = $this->movements([
            'Chest to Bar Pull Up',
            'Double Under',
            'Power Clean',
            'Row',
            'Ski Erg',
            'Thruster',
            'Toes to Bar',
            'Wall Ball Shot',
        ]);

        self::assertSame([
            'very frequent' => ['Toes to Bar', 'Double Under', 'Wall Ball Shot'],
            'regular' => ['Chest to Bar Pull Up', 'Power Clean', 'Ski Erg'],
        ], $provider->availableFrequencyBands($movements));
        self::assertContains('Double Under + Toes to Bar', $provider->availableFrequentMovementPairs($movements));
        self::assertContains('Chest to Bar Pull Up + Thruster', $provider->availableFrequentMovementPairs($movements));
        self::assertSame(['Power Clean', 'Wall Ball Shot', 'Row', 'Chest to Bar Pull Up'], $provider->availableRecentGeneratedTemplateMovements($movements));
        self::assertSame(['Power Clean', 'Chest to Bar Pull Up', 'Wall Ball Shot', 'Thruster'], $provider->availableOverusedRotationAnchors($movements));
    }

    public function testCompetitionGuidanceKeepsExistingPromptWording(): void
    {
        $provider = new CompetitionMovementFrequencyGuidanceProvider();

        $guidance = $provider->buildPromptGuidance(
            new WorkoutGeneration(),
            $this->movements([
                'Chest to Bar Pull Up',
                'Double Under',
                'Power Clean',
                'Row',
                'Ski Erg',
                'Thruster',
                'Toes to Bar',
                'Wall Ball Shot',
            ])
        );

        self::assertStringContainsString('Competition movement recurrence guidance:', $guidance);
        self::assertStringContainsString('very frequent available movements: Toes to Bar, Double Under, Wall Ball Shot.', $guidance);
        self::assertStringContainsString('regular available movements: Chest to Bar Pull Up, Power Clean, Ski Erg.', $guidance);
        self::assertStringContainsString('Strong rotation rule for this generation: no movement is mandatory', $guidance);
        self::assertStringContainsString('Power Clean + Chest to Bar Pull Up is recurring too often', $guidance);
    }

    public function testRejectedCompetitionClusterThrowsSameMessage(): void
    {
        $provider = new CompetitionMovementFrequencyGuidanceProvider();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Generated Competition workout selected an overused movement cluster (Chest to Bar Pull Up + Thruster + Row) without mandatory movements.');

        $provider->assertNoRejectedMovementCluster($this->movements([
            'Row',
            'Chest to Bar Pull Up',
            'Thruster',
        ]));
    }

    /**
     * @param list<string> $names
     *
     * @return list<Movement>
     */
    private function movements(array $names): array
    {
        $difficulty = new MovementDifficulty(MovementDifficultyEnum::INTERMEDIATE);
        $movementType = new MovementType(MovementTypeEnum::CARDIO);
        $movements = [];

        foreach ($names as $name) {
            $movements[] = new Movement($name, $difficulty, $movementType);
        }

        return $movements;
    }
}
