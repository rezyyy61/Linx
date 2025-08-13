#!/usr/bin/env bash
set -euo pipefail

WORKDIR="/var/www/html"

# Composer install (dev)
if [ -f "$WORKDIR/composer.json" ] && [ ! -d "$WORKDIR/vendor" ]; then
  echo "[entrypoint] vendor missing -> composer install"
  composer install --no-interaction --prefer-dist
fi

# Ensure .env exists and key set (dev)
if [ -f "$WORKDIR/.env.example" ] && [ ! -f "$WORKDIR/.env" ]; then
  cp "$WORKDIR/.env.example" "$WORKDIR/.env" || true
  php artisan key:generate || true
fi

exec "$@"
