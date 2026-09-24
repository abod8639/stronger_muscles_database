#!/bin/sh
set -e

# Set directory permissions for Laravel runtime
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true

# Storage link
if [ ! -L /var/www/html/public/storage ]; then
    echo "Creating storage symlink..."
    php artisan storage:link --force || true
fi

# In production mode, warm up caches
if [ "$APP_ENV" = "production" ]; then
    echo "Optimizing framework caches for production..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Execute passed command
exec "$@"
