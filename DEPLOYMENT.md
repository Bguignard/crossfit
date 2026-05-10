# Deployment

The production API is deployed manually from the server with `scripts/deploy_prod.sh`.

## Requirements

- The Symfony project is checked out on the server, usually in `/var/www/crossfit`.
- `.env.local` exists on the server and defines production secrets:
  - `APP_ENV=prod`
  - `APP_DEBUG=0`
  - `APP_SECRET`
  - `DATABASE_URL`
- PHP, Composer, PostgreSQL access, Nginx and PHP-FPM are already installed.
- The deploy user can reload PHP-FPM with `sudo systemctl reload php8.4-fpm`.

## Deploy

From the server:

```bash
cd /var/www/crossfit
./scripts/deploy_prod.sh
```

The script updates `master`, installs production Composer dependencies, runs Doctrine migrations, clears and warms the Symfony cache, then reloads PHP-FPM.

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
