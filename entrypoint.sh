#!/bin/sh
set -e

echo "==> Initializing CargoTrace Production..."

# Auto-generate or ensure default APP_KEY
if [ -z "$APP_KEY" ]; then
    export APP_KEY="base64:gBPHtJFSMWM9mmRllA19v8R3shySpdezmJxdN/YOMc8="
fi

# Detect Railway MySQL environment variables automatically
if [ -n "$MYSQLHOST" ]; then
    export DB_HOST="$MYSQLHOST"
    export DB_PORT="${MYSQLPORT:-3306}"
    export DB_DATABASE="${MYSQLDATABASE:-railway}"
    export DB_USERNAME="${MYSQLUSER:-root}"
    export DB_PASSWORD="$MYSQLPASSWORD"
    export DB_CONNECTION="mysql"
fi

# If no MySQL host is configured yet, safely fallback to SQLite so site never crashes
if [ -z "$DB_HOST" ] || [ "$DB_HOST" = "127.0.0.1" ]; then
    echo "==> Setting up SQLite temporary fallback..."
    mkdir -p database
    touch database/database.sqlite
    export DB_CONNECTION=sqlite
    export DB_DATABASE=/var/www/html/database/database.sqlite
fi

# TEMP: Enable debug mode so 500 errors show their real message
export APP_DEBUG=true
export APP_ENV=local

# Clear old configuration cache (do NOT cache config so env vars take effect immediately)
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Run database migrations and seeders automatically - capture output for debugging
echo "==> Running database migrations..."
php artisan migrate --force 2>&1 || echo "WARNING: migrate had errors above"
echo "==> Running database seeders..."
php artisan db:seed --force 2>&1 || echo "WARNING: seed had errors above"

# Cache routes and views but NOT config (so env vars override .env)
php artisan route:cache || true
php artisan view:cache || true

PORT_TO_USE="${PORT:-8000}"
echo "==> CargoTrace is live on port $PORT_TO_USE"
exec php artisan serve --host=0.0.0.0 --port="$PORT_TO_USE"
