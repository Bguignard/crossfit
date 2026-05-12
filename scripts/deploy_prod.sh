#!/usr/bin/env bash

set -Eeuo pipefail

PROJECT_DIR="${PROJECT_DIR:-/var/www/crossfit}"
BRANCH="${BRANCH:-master}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-php8.4-fpm}"
MESSENGER_SERVICE="${MESSENGER_SERVICE:-monwod-symfony-messenger}"
RUN_MIGRATIONS=1

usage() {
    printf 'Usage: %s [--skip-migrations]\n' "$0"
    printf '\nEnvironment overrides:\n'
    printf '  PROJECT_DIR       Default: /var/www/crossfit\n'
    printf '  BRANCH            Default: master\n'
    printf '  PHP_BIN           Default: php\n'
    printf '  COMPOSER_BIN      Default: composer\n'
    printf '  PHP_FPM_SERVICE   Default: php8.4-fpm\n'
    printf '  MESSENGER_SERVICE Default: monwod-symfony-messenger\n'
}

log() {
    printf '\n==> %s\n' "$1"
}

while [ "$#" -gt 0 ]; do
    case "$1" in
        --skip-migrations)
            RUN_MIGRATIONS=0
            shift
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            printf 'Unknown option: %s\n\n' "$1" >&2
            usage >&2
            exit 1
            ;;
    esac
done

export APP_ENV=prod
export APP_DEBUG=0

log "Changing directory to ${PROJECT_DIR}"
cd "$PROJECT_DIR"

log "Updating code from origin/${BRANCH}"
git fetch origin "$BRANCH"
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"

log "Installing production dependencies"
"$COMPOSER_BIN" install --no-dev --prefer-dist --no-interaction --optimize-autoloader

if [ "$RUN_MIGRATIONS" -eq 1 ]; then
    log "Running Doctrine migrations"
    "$PHP_BIN" bin/console doctrine:migrations:migrate --env=prod --no-interaction
else
    log "Skipping Doctrine migrations"
fi

log "Clearing production cache"
"$PHP_BIN" bin/console cache:clear --env=prod

log "Warming production cache"
"$PHP_BIN" bin/console cache:warmup --env=prod

log "Stopping Messenger workers so systemd restarts them with fresh code"
"$PHP_BIN" bin/console messenger:stop-workers --env=prod || true

log "Reloading ${PHP_FPM_SERVICE}"
sudo systemctl reload "$PHP_FPM_SERVICE"

if systemctl cat "$MESSENGER_SERVICE" >/dev/null 2>&1; then
    log "Restarting ${MESSENGER_SERVICE}"
    sudo systemctl restart "$MESSENGER_SERVICE"
else
    log "Messenger service ${MESSENGER_SERVICE} is not installed; skipping restart"
fi

log "Deployment completed"
