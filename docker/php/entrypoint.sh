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

# public/ is shared with host Nginx. Refresh immutable Vite assets from image.
rm -rf public/build
cp -a /opt/keuangan-public-build public/build

chown -R www-data:www-data bootstrap/cache storage public/build

exec "$@"
