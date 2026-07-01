<?php

namespace App\Tests;

use App\Command\BackfillAiGenerationUsageCostsCommand;
use App\Entity\WorkoutGeneration\WorkoutAiGenerationUsage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class BackfillAiGenerationUsageCostsCommandTest extends AbstractIntegrationTest
{
    public function testDryRunDoesNotPersistEstimatedCosts(): void
    {
        $usageId = $this->persistUsage('gpt-5.4-mini-2026-03-17', 1000, 1000);
        $command = $this->getService(BackfillAiGenerationUsageCostsCommand::class);
        self::assertInstanceOf(BackfillAiGenerationUsageCostsCommand::class, $command);

        $tester = new CommandTester($command);

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertStringContainsString('Rows priceable: 1', $tester->getDisplay());
        self::assertStringContainsString('Rows updated: 0', $tester->getDisplay());
        $this->getEntityManager()->clear();

        $usage = $this->getRepository(WorkoutAiGenerationUsage::class)->find($usageId);
        self::assertInstanceOf(WorkoutAiGenerationUsage::class, $usage);
        self::assertNull($usage->getEstimatedCostUsd());
    }

    public function testWriteBackfillsOnlyPriceableUsageRows(): void
    {
        $priceableUsageId = $this->persistUsage('gpt-5.4-mini-2026-03-17', 1000, 1000);
        $unknownUsageId = $this->persistUsage('unknown-model', 1000, 1000);
        $command = $this->getService(BackfillAiGenerationUsageCostsCommand::class);
        self::assertInstanceOf(BackfillAiGenerationUsageCostsCommand::class, $command);

        $tester = new CommandTester($command);

        self::assertSame(Command::SUCCESS, $tester->execute(['--write' => true]));
        self::assertStringContainsString('Rows inspected: 2', $tester->getDisplay());
        self::assertStringContainsString('Rows priceable: 1', $tester->getDisplay());
        self::assertStringContainsString('Rows updated: 1', $tester->getDisplay());
        self::assertStringContainsString('Unpriced models: unknown-model', $tester->getDisplay());
        $this->getEntityManager()->clear();

        $priceableUsage = $this->getRepository(WorkoutAiGenerationUsage::class)->find($priceableUsageId);
        self::assertInstanceOf(WorkoutAiGenerationUsage::class, $priceableUsage);
        self::assertSame('0.009000', $priceableUsage->getEstimatedCostUsd());

        $unknownUsage = $this->getRepository(WorkoutAiGenerationUsage::class)->find($unknownUsageId);
        self::assertInstanceOf(WorkoutAiGenerationUsage::class, $unknownUsage);
        self::assertNull($unknownUsage->getEstimatedCostUsd());
    }

    private function persistUsage(string $model, int $promptTokens, int $completionTokens): string
    {
        $usage = new WorkoutAiGenerationUsage(
            WorkoutAiGenerationUsage::ACTOR_ANONYMOUS,
            WorkoutAiGenerationUsage::ENDPOINT_WORKOUT,
            'workout',
            'success',
            true,
            null,
            'visitor-hash',
            [
                'model' => $model,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $promptTokens + $completionTokens,
                'estimated_cost_usd' => null,
            ],
        );

        $this->getEntityManager()->persist($usage);
        $this->getEntityManager()->flush();

        return (string) $usage->getId();
    }
}
