# syntax=docker/dockerfile:1.7

FROM composer:2 AS composer_deps
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-interaction --no-scripts

FROM node:22-bookworm-slim AS node_tools

FROM php:8.4-fpm-bookworm AS php_runtime

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libpq-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_pgsql \
        zip \
    && apt-get purge -y --auto-remove \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libpq-dev \
        libzip-dev \
    && apt-get install -y --no-install-recommends \
        libfreetype6 \
        libicu72 \
        libjpeg62-turbo \
        libpng16-16 \
        libpq5 \
        libzip4 \
    && rm -rf /var/lib/apt/lists/*

FROM php_runtime AS frontend_build
COPY --from=node_tools /usr/local/bin/node /usr/local/bin/node
COPY --from=node_tools /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx
WORKDIR /app
COPY --from=composer_deps /app ./
RUN npm ci --no-audit --no-fund
RUN php artisan package:discover --ansi \
    && php artisan wayfinder:generate --with-form
RUN npm run build

FROM php_runtime AS runtime

WORKDIR /var/www/keuangan

COPY --from=node_tools /usr/local/bin/node /usr/local/bin/node
COPY --from=node_tools /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --chown=www-data:www-data . .
COPY --from=composer_deps --chown=www-data:www-data /app/vendor ./vendor
COPY --from=frontend_build --chown=www-data:www-data /app/public/build ./public/build
COPY --from=frontend_build --chown=www-data:www-data /app/public/build /opt/keuangan-public-build
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-production.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/10-opcache.ini
COPY docker/php/entrypoint.sh /usr/local/bin/keuangan-entrypoint

RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx \
    && chmod 0755 /usr/local/bin/keuangan-entrypoint \
    && mkdir -p \
        bootstrap/cache \
        storage/app/private \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && chown -R www-data:www-data bootstrap/cache storage

EXPOSE 9000
ENTRYPOINT ["keuangan-entrypoint"]
CMD ["php-fpm", "-F"]
