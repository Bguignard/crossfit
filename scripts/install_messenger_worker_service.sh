#!/usr/bin/env bash

set -Eeuo pipefail

PROJECT_DIR="${PROJECT_DIR:-/var/www/crossfit}"
PHP_BIN="${PHP_BIN:-/usr/bin/php}"
SERVICE_NAME="${SERVICE_NAME:-monwod-symfony-messenger}"
SERVICE_USER="${SERVICE_USER:-ubuntu}"
SERVICE_GROUP="${SERVICE_GROUP:-www-data}"
TIME_LIMIT="${TIME_LIMIT:-3600}"
MEMORY_LIMIT="${MEMORY_LIMIT:-256M}"

usage() {
    printf 'Usage: sudo %s\n' "$0"
    printf '\nEnvironment overrides:\n'
    printf '  PROJECT_DIR    Default: /var/www/crossfit\n'
    printf '  PHP_BIN        Default: /usr/bin/php\n'
    printf '  SERVICE_NAME   Default: monwod-symfony-messenger\n'
    printf '  SERVICE_USER   Default: ubuntu\n'
    printf '  SERVICE_GROUP  Default: www-data\n'
    printf '  TIME_LIMIT     Default: 3600\n'
    printf '  MEMORY_LIMIT   Default: 256M\n'
}

if [ "${1:-}" = "-h" ] || [ "${1:-}" = "--help" ]; then
    usage
    exit 0
fi

if [ "$(id -u)" -ne 0 ]; then
    printf 'This script must be run with sudo/root because it writes a systemd unit.\n' >&2
    exit 1
fi

SERVICE_FILE="/etc/systemd/system/${SERVICE_NAME}.service"

cat > "$SERVICE_FILE" <<UNIT
[Unit]
Description=MonWOD Symfony Messenger worker
After=network.target postgresql.service

[Service]
Type=simple
User=${SERVICE_USER}
Group=${SERVICE_GROUP}
WorkingDirectory=${PROJECT_DIR}
Environment=APP_ENV=prod
Environment=APP_DEBUG=0
ExecStart=${PHP_BIN} -d memory_limit=${MEMORY_LIMIT} bin/console messenger:consume async --env=prod --time-limit=${TIME_LIMIT} --memory-limit=${MEMORY_LIMIT}
Restart=always
RestartSec=10
KillSignal=SIGTERM

[Install]
WantedBy=multi-user.target
UNIT

systemctl daemon-reload
systemctl enable "$SERVICE_NAME"
systemctl restart "$SERVICE_NAME"
systemctl status "$SERVICE_NAME" --no-pager
