#!/bin/sh
set -e

cd /var/www/html

# Render routes public traffic to $PORT — point nginx at it.
# Defaults to 10000 locally so `docker run` without -e PORT works.
NGINX_PORT="${PORT:-10000}"
sed -i "s/^\(\s*\)listen [0-9]\+;/\1listen ${NGINX_PORT};/" /etc/nginx/sites-enabled/default
nginx -t

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
