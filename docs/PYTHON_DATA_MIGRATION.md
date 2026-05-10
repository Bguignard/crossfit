# Python Data Migration

## Goal

Move existing crawler data from `crossfit-analyser` into the Symfony canonical database using the `competition-results.v1` import contract.

## Export From Python

From the Python project:

```bash
.venv/bin/python scripts/export_symfony_import.py exports/symfony-competition-results.v1.json
```

This writes:

- `exports/symfony-competition-results.v1.json`
- `exports/symfony-competition-results.v1.json.report.json`

The report contains exported counts, skipped rows, duplicate source identities, and synthesized athlete source profiles.

## Import Into Symfony

Copy the JSON export to the Symfony server, then run:

```bash
php bin/console app:import:competition-results var/imports/symfony-competition-results.v1.json --env=prod --no-debug
```

For large imports, keep the default batch size or tune it explicitly:

```bash
php bin/console app:import:competition-results var/imports/symfony-competition-results.v1.json --env=prod --no-debug --batch-size=500
```

The command is idempotent. Running the same import again should update existing source identities instead of creating duplicates.

## Local Validation

On a clean test database:

```bash
php bin/console doctrine:database:drop --env=test --force --if-exists
php bin/console doctrine:database:create --env=test
php bin/console doctrine:migrations:migrate --env=test --no-interaction
php -d memory_limit=1G bin/console app:import:competition-results var/symfony-import-sample.json --env=test --no-debug --batch-size=500
```

Expected current local export counts:

```text
workouts: 3738
athletes: 4890
competitions: 65
events: 3870
results: 26860
```

Re-running the import should report the same counts as `updated` and `0 failed`.

## Representative Checks

After import:

```bash
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM workout WHERE source_name IS NOT NULL" --env=test --no-debug
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM athlete" --env=test --no-debug
php bin/console doctrine:query:sql "SELECT a.display_name, COUNT(wr.id) AS results FROM athlete a JOIN workout_result wr ON wr.athlete_id = a.id WHERE a.display_name ILIKE '%Toomey%' GROUP BY a.id, a.display_name ORDER BY results DESC LIMIT 5" --env=test --no-debug
```

The current local export gives `Tia-Clair Toomey` 233 imported results.
