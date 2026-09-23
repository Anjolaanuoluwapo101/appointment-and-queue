#!/bin/sh
set -e

cd /var/www/html

# Runtime wiring (env vars are only available at container start,
# so caching + storage link happen here, not at build time).
php artisan storage:link --no-interaction || true
# NOTE: no route:cache — routes/web.php uses closures, which cannot be cached.
php artisan config:cache --no-interaction
php artisan view:cache --no-interaction

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

# Hand off to supervisord (nginx, php-fpm, queue worker, reverb, scheduler).
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/app.conf
