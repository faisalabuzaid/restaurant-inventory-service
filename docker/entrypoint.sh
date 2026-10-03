#!/usr/bin/env sh
# Boots the Laravel app from a fresh clone: installs PHP deps, prepares .env,
# creates the SQLite file and migrates + seeds. Idempotent, so it is safe to
# run on every container start.
set -eu

cd /app

if [ ! -f vendor/autoload.php ]; then
    echo "[entrypoint] installing composer dependencies"
    composer install --no-interaction --prefer-dist --no-progress
fi

if [ ! -f .env ]; then
    echo "[entrypoint] creating .env from .env.example"
    cp .env.example .env
fi

if ! grep -Eq '^APP_KEY=.+' .env; then
    echo "[entrypoint] generating APP_KEY"
    php artisan key:generate --force --no-interaction
fi

mkdir -p database storage/logs
[ -f database/database.sqlite ] || touch database/database.sqlite

echo "[entrypoint] running migrations and seeders"
php artisan migrate --force --seed --no-interaction

exec "$@"
