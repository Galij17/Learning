#!/usr/bin/env bash
set -euo pipefail

cd /app

# Ensure storage directories exist (especially important when mounting an empty Railway Volume at /app/storage)
mkdir -p \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chmod -R 775 storage bootstrap/cache

# Recreate public/storage symlink if missing
if [ ! -L public/storage ]; then
    rm -rf public/storage
    php artisan storage:link || true
fi

# Wait for MySQL and run migrations
echo "Waiting for database and running migrations..."
max_attempts=15
attempt=1
until php artisan migrate --force; do
    if [ "$attempt" -ge "$max_attempts" ]; then
        echo "Database migration failed after ${max_attempts} attempts."
        exit 1
    fi
    echo "Database not ready yet (attempt ${attempt}/${max_attempts}), retrying in 2s..."
    attempt=$((attempt + 1))
    sleep 2
done

# Seed initial or enriched demo data when users table is empty or demo bank questions are missing
if php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$needsInitial = ! \App\Models\User::query()->exists();
$needsDemoEnrich = (bool) config("app.demo_mode") && \App\Models\Question::query()->count() < 10;
exit(($needsInitial || $needsDemoEnrich) ? 0 : 1);
'; then
    echo "Running demo seed..."
    php artisan db:seed --force
else
    echo "Demo seed already present; skipping."
fi

# Cache configuration, routes, and views for production performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Starting FrankenPHP on port ${PORT:-8080}..."
exec frankenphp run --config /etc/caddy/Caddyfile
