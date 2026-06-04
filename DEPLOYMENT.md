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
Doctrine migrations, clears and warms the Symfony cache, asks old Messenger
workers to stop, reloads PHP-FPM, then restarts the Messenger worker when the
systemd service exists.

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

Use the console dispatchers only as operational catch-up commands for requests
created before the Messenger worker was available or after an incident:

```bash
APP_ENV=prod APP_DEBUG=0 php bin/console app:performance-analysis:dispatch --limit=5 --env=prod --no-debug
APP_ENV=prod APP_DEBUG=0 php bin/console app:programming-generation:dispatch --limit=5 --env=prod --no-debug
```

The command requires `PYTHON_WORKER_BASE_URL` to point to the analyser service.
The analyser service must also have its OpenAI credentials configured.

## Smoke Test

After deployment:

```bash
curl -i https://api.monwod.fr/health
```

Expected response:

```json
{"status":"ok"}
```
