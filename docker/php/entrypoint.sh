#!/bin/sh
set -eu

mkdir -p \
    bootstrap/cache \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

# Only the FPM service publishes assets to the host Nginx shared directory.
if [ "${1:-}" = "php-fpm" ]; then
    rm -rf public/build
    cp -a /opt/keuangan-public-build public/build
fi

chown -R www-data:www-data bootstrap/cache storage

if [ -d public/build ]; then
    chown -R www-data:www-data public/build
fi

exec "$@"
