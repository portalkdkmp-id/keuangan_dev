#!/bin/sh
set -eu

APP_PATH="${APP_PATH:-/var/www/keuangan_dev}"
RUN_MIGRATIONS="${RUN_MIGRATIONS:-true}"

if [ "$(pwd -P)" != "$APP_PATH" ]; then
    echo "Error: checkout must be at $APP_PATH so host Nginx and PHP-FPM resolve identical paths." >&2
    exit 1
fi

if [ ! -f .env ]; then
    echo "Error: create and configure $APP_PATH/.env before deployment." >&2
    exit 1
fi

case "$RUN_MIGRATIONS" in
    true|false) ;;
    *) echo "Error: RUN_MIGRATIONS must be true or false." >&2; exit 1 ;;
esac

docker compose config --quiet
docker compose build --pull
docker compose stop queue scheduler >/dev/null 2>&1 || true
docker compose up -d --remove-orphans app
docker compose exec -T app php artisan optimize:clear

if [ "$RUN_MIGRATIONS" = "true" ]; then
    docker compose exec -T app php artisan migrate --force
fi

docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache
docker compose up -d --remove-orphans queue scheduler

running_services="$(docker compose ps --services --status running)"
for service in app queue scheduler; do
    echo "$running_services" | grep -qx "$service" || {
        echo "Error: service $service is not running." >&2
        docker compose logs --tail=100 "$service"
        exit 1
    }
done

docker compose ps

echo "Deployment complete. Migration enabled: $RUN_MIGRATIONS"
