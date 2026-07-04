#!/bin/sh
set -e

PORT="${PORT:-8080}"
APACHE_DOCUMENT_ROOT="${APACHE_DOCUMENT_ROOT:-/var/www/html/public}"

if [ -z "${APP_KEY:-}" ]; then
    export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    echo "APP_KEY is not set; generated a temporary key for this container. Set a persistent APP_KEY in Railway variables."
fi

printf 'Listen %s\n' "${PORT}" > /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
sed -ri "s!DocumentRoot .*!DocumentRoot ${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/000-default.conf

mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Pass all environment variables to a .env file so Apache/mod_php can read them
env > /var/www/html/.env

php artisan config:cache >/dev/null 2>&1 || true
php artisan route:cache >/dev/null 2>&1 || true
php artisan view:cache >/dev/null 2>&1 || true
php artisan storage:link --force >/dev/null 2>&1 || true

chown -R www-data:www-data storage bootstrap/cache .env

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

apache2ctl -t
echo "Starting Apache on port ${PORT}"
exec apache2-foreground
