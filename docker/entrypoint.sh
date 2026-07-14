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

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan storage:link --force >/dev/null 2>&1 || true

chown -R www-data:www-data storage bootstrap/cache

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
    if [ "${RUN_SEEDERS:-false}" = "true" ]; then
        php artisan db:seed --force
    fi
fi

if [ "${RUN_SCHEDULER:-true}" = "true" ]; then
    php artisan schedule:work &
fi

a2dismod mpm_event mpm_worker >/dev/null 2>&1 || true
a2enmod mpm_prefork >/dev/null 2>&1 || true

apache2ctl -t
echo "Starting Apache on port ${PORT}"
exec apache2-foreground
