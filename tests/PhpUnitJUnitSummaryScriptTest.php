<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;

use function MonWod\Tooling\PhpUnitJUnitSummary\compareSummaries;
use function MonWod\Tooling\PhpUnitJUnitSummary\renderTextComparison;
use function MonWod\Tooling\PhpUnitJUnitSummary\renderTextSummary;
use function MonWod\Tooling\PhpUnitJUnitSummary\summarizeFile;

require_once __DIR__.'/../scripts/summarize_phpunit_junit.php';

/**
 * @group unit
 */
final class PhpUnitJUnitSummaryScriptTest extends TestCase
{
    public function testSummarizeFileAggregatesSlowestClassesAndTests(): void
    {
        $path = sys_get_temp_dir().'/monwod-phpunit-junit-'.bin2hex(random_bytes(4)).'.xml';
        file_put_contents($path, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
  <testsuite name="Project Test Suite" tests="3" assertions="7" failures="1" errors="0" skipped="1" time="1.800">
    <testcase class="App\Tests\SlowIntegrationTest" name="testApiWorkflow" assertions="3" time="1.200" />
    <testcase class="App\Tests\FastServiceTest" name="testServiceRule" assertions="2" time="0.100" />
    <testcase class="App\Tests\SlowIntegrationTest" name="testSkippedWorkflow" assertions="2" time="0.500">
      <skipped />
    </testcase>
    <testcase class="App\Tests\FailingTest" name="testFailure" assertions="0" time="0.300">
      <failure message="Expected true." />
    </testcase>
  </testsuite>
</testsuites>
XML);

        $summary = summarizeFile($path, 2);

        self::assertSame(4, $summary['totals']['tests']);
        self::assertSame(7, $summary['totals']['assertions']);
        self::assertSame(1, $summary['totals']['failures']);
        self::assertSame(1, $summary['totals']['skipped']);
        self::assertSame('App\Tests\SlowIntegrationTest', $summary['slowestClasses'][0]['class']);
        self::assertSame(1.7, $summary['slowestClasses'][0]['time']);
        self::assertSame('testApiWorkflow', $summary['slowestTests'][0]['name']);
        self::assertSame('passed', $summary['slowestTests'][0]['status']);
        self::assertCount(2, $summary['slowestTests']);
        self::assertStringContainsString('Slowest classes:', renderTextSummary($summary));
        self::assertStringContainsString('App\Tests\SlowIntegrationTest::testApiWorkflow', renderTextSummary($summary));
    }

    public function testCompareSummariesReportsLargestClassDeltas(): void
    {
        $baselinePath = sys_get_temp_dir().'/monwod-phpunit-junit-baseline-'.bin2hex(random_bytes(4)).'.xml';
        $currentPath = sys_get_temp_dir().'/monwod-phpunit-junit-current-'.bin2hex(random_bytes(4)).'.xml';

        file_put_contents($baselinePath, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
  <testsuite name="Project Test Suite">
    <testcase class="App\Tests\WorkoutApiWorkflowTest" name="testWorkoutCatalog" assertions="3" time="20.000" />
    <testcase class="App\Tests\PrivateUserProfileApiTest" name="testProfile" assertions="2" time="10.000" />
  </testsuite>
</testsuites>
XML);
        file_put_contents($currentPath, <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
  <testsuite name="Project Test Suite">
    <testcase class="App\Tests\WorkoutApiWorkflowTest" name="testWorkoutCatalog" assertions="3" time="12.500" />
    <testcase class="App\Tests\PrivateUserProfileApiTest" name="testProfile" assertions="2" time="11.250" />
    <testcase class="App\Tests\NewFastServiceTest" name="testRule" assertions="1" time="0.250" />
  </testsuite>
</testsuites>
XML);

        $comparison = compareSummaries(summarizeFile($baselinePath), summarizeFile($currentPath), 2);

        self::assertSame(30.0, $comparison['baselineTotals']['time']);
        self::assertSame(24.0, $comparison['currentTotals']['time']);
        self::assertSame('App\Tests\WorkoutApiWorkflowTest', $comparison['classDeltas'][0]['class']);
        self::assertSame(-7.5, $comparison['classDeltas'][0]['delta']);
        self::assertSame('App\Tests\PrivateUserProfileApiTest', $comparison['classDeltas'][1]['class']);
        self::assertStringContainsString('Totals: 30.000s -> 24.000s (-6.000s)', renderTextComparison($comparison));
        self::assertStringContainsString('- -7.500s 20.000s -> 12.500s App\Tests\WorkoutApiWorkflowTest', renderTextComparison($comparison));
    }
}
