# Symfony Test Strategy

This document defines the current MonWOD backend test loop. The goal is to speed up local and agent iteration without reducing the full pre-merge safety net.

## Current State

- `phpunit.xml.dist` currently exposes one global PHPUnit suite over `tests/`.
- GitHub CI already separates `Quality` from `Tests`.
- CI must keep running the full PHPUnit suite before merge.
- `tests/AbstractIntegrationTest.php` reloads all Doctrine fixtures in `setUp()`, which is likely one of the main costs for DB/API tests.
- The suite now has a small seed of PHPUnit groups for obvious classes only. Groups are not exhaustive yet.

## Local Validation Paths

### Fast Local / Agent Loop

Use this path while iterating on a narrow backend change:

```bash
php -l path/to/touched.php
php bin/console lint:container --env=test
composer test:fast -- 'ClassNameOrMethodName'
```

`composer test:fast` is intentionally a targeted PHPUnit filter. Prefer filters that match the touched behavior, for example:

```bash
composer test:fast -- WorkoutCreatorServiceTest
composer test:fast -- 'WorkoutCreatorServiceTest::testCompetitionPromptUsesMovementFrequencyGuidanceFilteredByAllowedPool'
```

Seeded groups are available for broad local checks:

```bash
composer test:unit
composer test:integration
composer test:workflow
composer test:slow
composer test:no-slow
```

The positive group commands only run tests already tagged with the matching group. `composer test:no-slow` runs the full suite except tests tagged `slow`, including untagged tests. These commands are useful for local focus, but they are not a replacement for `composer test`.

Use `composer test:no-slow` only as a local iteration shortcut when the touched code is unrelated to the slow API/profile flows. The full suite is still required before merge.

The Composer PHPUnit scripts use `memory_limit=1G`, matching CI, because the full suite can exceed PHP's default 128M memory limit on local runs.

When a change touches Doctrine mapping or migrations, add:

```bash
php bin/console doctrine:schema:validate --env=test
```

### Full Pre-Merge Safety Net

Use this before merge, and keep it in CI:

```bash
composer quality
composer test
```

For DB-backed changes, run the same database preparation as CI before `composer test`:

```bash
php bin/console doctrine:database:drop --env=test --force --if-exists
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test --no-interaction
php bin/console doctrine:schema:validate --env=test
composer test
```

## Profiling Runtime

First measured full-suite profile, recorded on 2026-06-26 after #381:

- 253 tests, 1623 assertions.
- 98.138s in the JUnit report, 01:38.462 in PHPUnit output.
- 0 failures, 0 errors, 0 skipped.
- 278.50 MB peak memory.

Slowest classes from that profile:

```text
28.416s App\Tests\WorkoutApiWorkflowTest (38 tests)
17.114s App\Tests\PrivateUserProfileApiTest (21 tests)
4.515s App\Tests\InferWorkoutPrescriptionPatternsCommandTest (6 tests)
4.397s App\Tests\ProgrammingGenerationRequestModelTest (5 tests)
4.222s App\Tests\ImportCompetitionResultsCommandTest (5 tests)
3.724s App\Tests\SuggestOfficialCompetitionQualificationsCommandTest (5 tests)
3.275s App\Tests\ProductFixturesTest (4 tests)
2.896s App\Tests\WorkoutEnrichmentCommandTest (4 tests)
2.785s App\Tests\DispatchPerformanceAnalysisRequestsCommandTest (4 tests)
2.721s App\Tests\AdminDashboardMetricsTest (4 tests)
```

The current `slow` group contains only the two classes above 5 seconds in that profile.

Generate a PHPUnit JUnit report:

```bash
composer test:profile
```

Summarize the slowest classes and tests:

```bash
composer test:profile:summary
```

The summary script reads `var/reports/phpunit-junit.xml` by default. It can also summarize another report path:

```bash
php scripts/summarize_phpunit_junit.php var/reports/phpunit-junit.xml 20
```

Compare a baseline profile with a profile produced after an optimization:

```bash
cp var/reports/phpunit-junit.xml var/reports/phpunit-junit-baseline.xml
composer test:profile
composer test:profile:compare -- var/reports/phpunit-junit-baseline.xml var/reports/phpunit-junit.xml 20
```

Use this before changing fixture loading or splitting slow API classes. The comparison reports total runtime deltas and the largest class-level changes, which helps us verify that a local optimization improves the known bottlenecks instead of merely moving cost around.

This gives us a repeatable way to identify slow tests before changing fixtures, grouping, or parallelization.

## Recommended Categories

These are the categories we should introduce progressively with PHPUnit groups or separate suites after collecting runtime data:

- `unit`: pure model/service logic, no Symfony kernel and no database.
- `integration`: Symfony kernel, Doctrine repositories, DB state, API Platform serialization, authenticated API flows.
- `workflow`: Messenger, async dispatch handlers, command-to-worker integration, generated request lifecycle.
- `migration`: Doctrine migrations and schema validation.
- `external`: adapters around OpenAI, Python worker, geocoding, crawling, email, and other network-like boundaries; tests should use fakes by default.

Use PHPUnit 9-compatible annotations for now:

```php
/**
 * @group unit
 */
```

Current seed:

- `unit`: pure service/model/parser/report tests with no Symfony kernel or database.
- `integration`: API/DB full-stack tests, DB-backed command tests, repository/model persistence tests.
- `workflow`: Messenger dispatch, worker request processing, and generated request lifecycle tests.
- `slow`: currently `WorkoutApiWorkflowTest` and `PrivateUserProfileApiTest`, the only classes above 5 seconds in the first measured profile.

Do not retag everything at once. Continue from the slowest classes found by profiling and the clearly pure unit/service tests.

## Fixture Guidance

Do not optimize fixture loading blindly. First profile the suite and identify which integration/API classes pay the biggest setup cost.

Likely follow-ups:

- capture a baseline/current comparison before and after each fixture-loading experiment;
- split heavy integration fixtures from minimal API fixtures;
- reuse fixture setup safely where tests do not mutate shared state;
- isolate model/service tests that can avoid `AbstractIntegrationTest`;
- measure Dama Doctrine transaction behavior before changing purge/load semantics.

## Next Steps

1. Run `composer test:profile` locally or in a diagnostic CI job and attach the summary to issue #378.
2. Identify the slowest test classes and whether they are unit, integration, workflow, migration, or external-boundary tests.
3. Add PHPUnit groups or secondary suites only where the profile supports it.
4. Optimize fixture loading for the worst DB/API classes without deleting coverage.
5. Re-evaluate ParaTest after the suite is grouped and fixture/database isolation is understood.
