# Deployment

The production API is deployed automatically by GitHub Actions on pushes to
`master`. The manual script `scripts/deploy_prod.sh` remains the operational
fallback.

## Requirements

- The Symfony project is checked out on the server, usually in `/var/www/crossfit`.
- `.env.local` exists on the server and defines production secrets:
  - `APP_ENV=prod`
  - `APP_DEBUG=0`
  - `APP_SECRET`
  - `DATABASE_URL`
  - `MAILER_DSN`
  - `MAILER_FROM`
  - `FRONTEND_BASE_URL`
  - `PYTHON_WORKER_BASE_URL`
- PHP, Composer, PostgreSQL access, Nginx and PHP-FPM are already installed.
- The deploy user can reload PHP-FPM with `sudo systemctl reload php8.4-fpm`.
- The deploy user can restart the Messenger worker with
  `sudo systemctl restart monwod-symfony-messenger` once the service is installed.

## Automatic Deploy

GitHub Actions workflow: `.github/workflows/deploy.yml`

It runs on:

- every push to `master`;
- manual `workflow_dispatch`.

Required GitHub repository secrets:

- `PROD_SSH_HOST`: production server hostname or IP.
- `PROD_SSH_USER`: SSH user.
- `PROD_SSH_KEY`: private SSH key allowed to connect as the deploy user.

Optional GitHub repository secret:

- `PROD_SSH_PORT`: SSH port, defaults to `22`.

Optional GitHub repository variable:

- `PROD_PROJECT_DIR`: production checkout path, defaults to `/var/www/crossfit`.

The deploy user must be able to run:

```bash
cd /var/www/crossfit
./scripts/deploy_prod.sh
```

The script updates `master`, installs production Composer dependencies, runs
Doctrine migrations, clears and warms the Symfony cache, reloads PHP-FPM, then
restarts the Messenger worker when the systemd service exists. The Messenger
service is stopped before changing code or cache so a worker cannot load removed
container files during deploy.

## Manual Deploy

From the server:

```bash
cd /var/www/crossfit
./scripts/deploy_prod.sh
```

To deploy without running migrations:

```bash
./scripts/deploy_prod.sh --skip-migrations
```

## Configuration

The script defaults are suitable for the current server, but can be overridden:

```bash
PROJECT_DIR=/var/www/crossfit \
BRANCH=master \
PHP_BIN=php \
COMPOSER_BIN=composer \
PHP_FPM_SERVICE=php8.4-fpm \
MESSENGER_SERVICE=monwod-symfony-messenger \
./scripts/deploy_prod.sh
```

## Messenger Worker

Transactional account emails are sent immediately so password reset and account
validation do not depend on a queue worker. Messenger is still available for
asynchronous jobs and notifier messages, so keep the worker installed when those
features are used.

Install or refresh the worker service on the server:

```bash
cd /var/www/crossfit
sudo ./scripts/install_messenger_worker_service.sh
```

Useful overrides:

```bash
sudo PROJECT_DIR=/var/www/crossfit \
PHP_BIN=/usr/bin/php \
SERVICE_USER=ubuntu \
SERVICE_GROUP=www-data \
./scripts/install_messenger_worker_service.sh
```

Operate the worker:

```bash
sudo systemctl status monwod-symfony-messenger
sudo systemctl restart monwod-symfony-messenger
sudo journalctl -u monwod-symfony-messenger -f
```

Debug the queue:

```bash
php bin/console dbal:run-sql --force-fetch "SELECT COUNT(*) FROM messenger_messages;"
php bin/console messenger:failed:show --env=prod
php bin/console messenger:failed:retry --env=prod
php bin/console messenger:failed:remove --all --env=prod
```

## Personal AI Analysis And Programming

Personal performance analyses and personal programming generations are queued
from the profile page and dispatched to the Python analyser through Symfony
Messenger. Users are limited to one analysis request every 24 hours. The
Messenger worker must be running, otherwise requests remain visible as queued.
Queued requests keep a `messengerEnqueuedAt` timestamp. If a request is still
queued and has no recent Messenger enqueue attempt, the profile requests API and
the deployment catch-up command enqueue it again. This avoids orphaned requests
remaining visible as queued when no corresponding Messenger message exists.

Use the console dispatchers only as operational catch-up commands for requests
created before the Messenger worker was available or after an incident:

```bash
APP_ENV=prod APP_DEBUG=0 php bin/console app:performance-analysis:dispatch --limit=5 --env=prod --no-debug
APP_ENV=prod APP_DEBUG=0 php bin/console app:programming-generation:dispatch --limit=5 --env=prod --no-debug
```

The command requires `PYTHON_WORKER_BASE_URL` to point to the analyser service.
The analyser service must also have its OpenAI credentials configured.

## Competition Geography Maintenance

Competition imports can create or update raw locations. Keep structured
competition geography fresh with the maintenance script:

```bash
cd /var/www/crossfit
./scripts/run_competition_geo_maintenance.sh
```

The script runs local normalization first, then geocoding with the persistent
cache. It uses a `flock` lock so overlapping cron runs exit cleanly.

Recommended cron:

```cron
37 * * * * cd /var/www/crossfit && ./scripts/run_competition_geo_maintenance.sh >> var/log/competition-geo-maintenance.log 2>&1
```

Useful overrides:

```bash
PROJECT_DIR=/var/www/crossfit \
PHP_BIN=/usr/bin/php \
LIMIT=5000 \
./scripts/run_competition_geo_maintenance.sh
```

The regular cron intentionally does not pass `--retry-unresolved`: new
locations are geocoded, but addresses already known as unresolved are not
retried forever. After improving the geocoder or correcting source data, run a
manual retry:

```bash
php bin/console app:competitions:geocode --limit=5000 --retry-unresolved --env=prod
php bin/console app:competitions:geocode --limit=5000 --retry-unresolved --write --env=prod
```

## Known Competition Results Crawl

Known scoring.fit and Competition Corner competitions are discovered by the
Python crawler before or during the event. After they have ended, Symfony can
ask the Python worker to crawl their WODs, athletes and results, then import the
returned `competition-results.v1` payload.

The command requires the Python worker to be reachable from the Symfony server:

```env
PYTHON_WORKER_BASE_URL=http://127.0.0.1:8000
PYTHON_WORKER_TIMEOUT_SECONDS=300
```

When PHP and Python run on the same server, `http://127.0.0.1:8000` is the
preferred URL. If Python is hosted elsewhere, override `PYTHON_WORKER_BASE_URL`
in production `.env.local`.

Manual run:

```bash
cd /var/www/crossfit
flock -n /tmp/monwod-crawl-known-results.lock php bin/console app:competitions:crawl-known-results --env=prod --limit=20
```

Use `--retry-recent` for an explicit manual retry after a transient worker
bug, outage or configuration issue. It ignores the recent-attempt cooldown but
still skips competitions that already have imported results.

Use `--force` only when you intentionally want to recrawl the selected
competitions even if results already exist. The regular cron should omit both
options so recent failed/empty attempts are not retried continuously.

Recommended daily cron:

```cron
25 5 * * * cd /var/www/crossfit && flock -n /tmp/monwod-crawl-known-results.lock php bin/console app:competitions:crawl-known-results --env=prod --limit=20 >> var/log/known-competition-results-crawl.log 2>&1
```

## Competition Logo Backfill

Competition imports can create future, live or past competitions before their
logo is known locally. Symfony can enrich missing Competition Corner and
scoring.fit logos after the competition exists in the database:

```bash
cd /var/www/crossfit
php bin/console app:competitions:backfill-logos --env=prod --limit=30
```

Useful options:

- `--source=competition_corner` or `--source=scoring_fit` limits the source.
- `--external-id=20465` targets one known competition.
- `--dry-run` fetches and reports logos without writing.
- `--force` refreshes existing logos and clears stale ones if the source no
  longer exposes a logo.

The regular cron should omit `--force` so it only enriches competitions still
missing a logo. Use a `flock` lock because the command performs external HTTP
requests.

Recommended daily cron:

```cron
45 5 * * * cd /var/www/crossfit && flock -n /tmp/monwod-backfill-competition-logos.lock php bin/console app:competitions:backfill-logos --env=prod --limit=30 >> var/log/competition-logo-backfill.log 2>&1
```

## Production Cron Schedule

Current recommended production schedule:

```cron
0 */2 * * * cd /var/www/crossfit-analyser && SYMFONY_SYNC_LOCAL_IMPORT=1 SYMFONY_SYNC_PROJECT_PATH=/var/www/crossfit .venv/bin/python scripts/sync_symfony_after_crawl.py --force-sync -- --skip-scoring-fit --crossfit-games-athlete-batch-size 10 >> logs/symfony-sync.log 2>&1
45 */2 * * * cd /var/www/crossfit && ./scripts/run_competition_geo_maintenance.sh >> var/log/competition-geo-maintenance.log 2>&1
20 * * * * cd /var/www/crossfit && APP_DEBUG=0 php bin/console app:athletes:backfill-games-photos --limit=50 --env=prod --no-debug >> var/log/games-photo-backfill.log 2>&1
15 3 * * * /usr/local/bin/monwod-backup.sh >> /var/log/monwod-backup.log 2>&1
25 5 * * * cd /var/www/crossfit && flock -n /tmp/monwod-crawl-known-results.lock php bin/console app:competitions:crawl-known-results --env=prod --limit=20 >> var/log/known-competition-results-crawl.log 2>&1
45 5 * * * cd /var/www/crossfit && flock -n /tmp/monwod-backfill-competition-logos.lock php bin/console app:competitions:backfill-logos --env=prod --limit=30 >> var/log/competition-logo-backfill.log 2>&1
```

Cron expression warning: `* */2 * * *` means "every minute during every even
hour", not "every two hours". Use `0 */2 * * *` for once every two hours.

## Smoke Test

After deployment:

```bash
curl -i https://api.monwod.fr/health
```

Expected response:

```json
{"status":"ok"}
```
