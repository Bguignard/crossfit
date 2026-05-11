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
Doctrine migrations, clears and warms the Symfony cache, then reloads PHP-FPM.

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
./scripts/deploy_prod.sh
```

## Smoke Test

After deployment:

```bash
curl -i https://api.monwod.fr/health
```

Expected response:

```json
{"status":"ok"}
```
