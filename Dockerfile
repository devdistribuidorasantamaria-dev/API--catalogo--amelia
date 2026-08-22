# ---- Stage 1: dependencias PHP ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev

# ---- Stage 2: assets del panel (Vite) ----
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# ---- Stage 3: runtime (php-fpm + nginx) ----
FROM php:8.3-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev libjpeg62-turbo-dev libpng-dev libwebp-dev libzip-dev libonig-dev \
        nginx supervisor curl unzip \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo_pgsql pgsql gd exif zip mbstring \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && rm -f /etc/nginx/sites-enabled/default 2>/dev/null || true

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
