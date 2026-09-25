#!/bin/sh
set -eu

cd /var/www/html/backend

if [ "${DB_CONNECTION:-}" = "sqlite" ]; then
    database_path="${DB_DATABASE:-/var/www/html/backend/database/database.sqlite}"
    mkdir -p "$(dirname "$database_path")"
    touch "$database_path"
fi

php artisan storage:link --force >/dev/null 2>&1 || true

if [ "${RUN_MIGRATIONS:-0}" = "1" ]; then
    php artisan migrate --force
fi

exec apache2-foreground
