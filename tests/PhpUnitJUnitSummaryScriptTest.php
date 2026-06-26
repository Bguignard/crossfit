<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;

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
}
