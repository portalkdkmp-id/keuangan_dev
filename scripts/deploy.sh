#!/bin/sh
set -eu

APP_PATH="${APP_PATH:-/var/www/html/keuangan_dev}"

if [ "$(pwd -P)" != "$APP_PATH" ]; then
    echo "Error: checkout must be at $APP_PATH so host Nginx and PHP-FPM resolve identical paths." >&2
    exit 1
fi

if [ ! -f .env ]; then
    echo "Error: create and configure $APP_PATH/.env before deployment." >&2
    exit 1
fi

docker compose build
docker compose up -d --remove-orphans
docker compose exec -T app php artisan optimize:clear
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache

if docker compose --profile queue ps --services --status running | grep -qx queue; then
    docker compose exec -T queue php artisan queue:restart
fi

docker compose ps

echo "Deployment complete. Database migrations were NOT run."
echo "When explicitly required: docker compose exec app php artisan migrate --force"
