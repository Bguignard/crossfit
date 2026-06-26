# Symfony Test Strategy

This document defines the current MonWOD backend test loop. The goal is to speed up local and agent iteration without reducing the full pre-merge safety net.

## Current State

- `phpunit.xml.dist` currently exposes one global PHPUnit suite over `tests/`.
- GitHub CI already separates `Quality` from `Tests`.
- CI must keep running the full PHPUnit suite before merge.
- `tests/AbstractIntegrationTest.php` reloads all Doctrine fixtures in `setUp()`, which is likely one of the main costs for DB/API tests.
- The suite does not use PHPUnit groups yet.

## Local Validation Paths

### Fast Local / Agent Loop

Use this path while iterating on a narrow backend change:

```bash
php -l path/to/touched.php
php bin/console lint:container --env=test
composer test:fast -- 'ClassNameOrMethodName'
```

`composer test:fast` is intentionally a targeted PHPUnit filter. It does not claim to be a complete fast suite yet, because no groups exist today. Prefer filters that match the touched behavior, for example:

```bash
composer test:fast -- WorkoutCreatorServiceTest
composer test:fast -- 'WorkoutCreatorServiceTest::testCompetitionPromptUsesMovementFrequencyGuidanceFilteredByAllowedPool'
```

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

This gives us a repeatable way to identify slow tests before changing fixtures, grouping, or parallelization.

## Recommended Categories

These are the categories we should introduce progressively with PHPUnit groups or separate suites after collecting runtime data:

- `unit`: pure model/service logic, no Symfony kernel and no database.
- `integration`: Symfony kernel, Doctrine repositories, DB state, API Platform serialization, authenticated API flows.
- `workflow`: Messenger, async dispatch handlers, command-to-worker integration, generated request lifecycle.
- `migration`: Doctrine migrations and schema validation.
- `external`: adapters around OpenAI, Python worker, geocoding, crawling, email, and other network-like boundaries; tests should use fakes by default.

Suggested future group names:

```php
#[Group('unit')]
#[Group('integration')]
#[Group('workflow')]
#[Group('migration')]
#[Group('external')]
```

Do not retag everything at once. Start with the slowest classes found by profiling and the clearly pure unit/service tests.

## Fixture Guidance

Do not optimize fixture loading blindly. First profile the suite and identify which integration/API classes pay the biggest setup cost.

Likely follow-ups:

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
