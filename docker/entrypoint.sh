#!/bin/bash
set -e

# Ensure storage and bootstrap directories exist with correct permissions
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/storage/app/public \
         /var/www/html/storage/app/private/backups \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Wait for PostgreSQL database if DB_HOST is set
if [ -n "$DB_HOST" ] && [ "$DB_CONNECTION" = "pgsql" ]; then
    echo "Waiting for PostgreSQL ($DB_HOST:$DB_PORT)..."
    until pg_isready -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-postgres}" > /dev/null 2>&1; do
        sleep 1
    done
    echo "PostgreSQL is ready!"
fi

# Ensure storage link exists
if [ ! -L /var/www/html/public/storage ]; then
    php artisan storage:link || true
fi

exec "$@"
