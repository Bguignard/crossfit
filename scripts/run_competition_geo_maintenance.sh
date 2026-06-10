#!/usr/bin/env bash

set -Eeuo pipefail

PROJECT_DIR="${PROJECT_DIR:-/var/www/crossfit}"
PHP_BIN="${PHP_BIN:-php}"
LIMIT="${LIMIT:-5000}"
LOCK_FILE="${LOCK_FILE:-/tmp/monwod-competition-geo-maintenance.lock}"

cd "$PROJECT_DIR"

(
    if ! flock -n 9; then
        printf 'Competition geography maintenance is already running.\n'
        exit 0
    fi

    printf 'Normalizing competition geography...\n'
    "$PHP_BIN" bin/console app:competitions:normalize-geo \
        --limit="$LIMIT" \
        --write \
        --env=prod \
        --no-debug

    printf 'Geocoding competition geography...\n'
    "$PHP_BIN" bin/console app:competitions:geocode \
        --limit="$LIMIT" \
        --write \
        --env=prod \
        --no-debug
) 9>"$LOCK_FILE"
