# syntax=docker/dockerfile:1
#
# Appointment System — single-container production image:
#   nginx (port 80) + PHP-FPM + queue worker + Reverb + scheduler
# Database is external (Supabase Postgres via DB_* env vars).
#
# Build:  docker build -t appointment-system .
# Run:    docker run -p 10000:10000 --env-file .env appointment-system
#
# NOTE: VITE_* values are baked into the JS at build time, so the public
# Reverb endpoint must be passed as build args on Render (see render.yaml):
#   --build-arg VITE_REVERB_HOST=<service>.onrender.com
#   --build-arg VITE_REVERB_PORT=443
#   --build-arg VITE_REVERB_SCHEME=https

# ---------- Stage 1: frontend assets ----------
FROM node:22-alpine AS frontend
ARG VITE_REVERB_APP_KEY=""
ARG VITE_REVERB_HOST="localhost"
ARG VITE_REVERB_PORT="443"
ARG VITE_REVERB_SCHEME="https"
ARG VITE_APP_NAME="Appointment System"
ENV VITE_REVERB_APP_KEY=$VITE_REVERB_APP_KEY \
    VITE_REVERB_HOST=$VITE_REVERB_HOST \
    VITE_REVERB_PORT=$VITE_REVERB_PORT \
    VITE_REVERB_SCHEME=$VITE_REVERB_SCHEME \
    VITE_APP_NAME=$VITE_APP_NAME
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts
COPY resources resources
COPY vite.config.js ./
COPY public public
RUN npm run build

# ---------- Stage 2: PHP dependencies ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist

# ---------- Stage 3: runtime ----------
FROM php:8.3-fpm AS app

# System deps + PHP extensions (pgsql for Supabase, zip/xml/gd for
# maatwebsite/excel + dompdf, sockets/pcntl for Reverb + queue worker)
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip sockets opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# App source (exclude via .dockerignore: node_modules, .git, .env, tests noise)
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer dump-autoload --optimize --no-dev --no-interaction
RUN php artisan package:discover --ansi --no-interaction || true

# Container configs
COPY docker/nginx.conf /etc/nginx/sites-enabled/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/app.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p /var/log/supervisor /var/run/php \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80 8080

ENTRYPOINT ["entrypoint.sh"]
