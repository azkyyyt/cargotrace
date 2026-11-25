#!/bin/sh
set -e

echo "==> Initializing CargoTrace Production..."

# Cache configuration, routes, and views for optimal performance
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Run database migrations if database connection is available
if [ -n "$DB_HOST" ] && [ "$DB_HOST" != "127.0.0.1" ]; then
    echo "==> Running cloud database migrations..."
    php artisan migrate --force || true
fi

PORT_TO_USE="${PORT:-8000}"
echo "==> CargoTrace is live on port $PORT_TO_USE"
exec php artisan serve --host=0.0.0.0 --port="$PORT_TO_USE"
