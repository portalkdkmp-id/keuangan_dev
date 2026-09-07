#!/bin/sh
set -eu

COMPOSE_FILE="compose.local.yaml"

case "${1:-up}" in
    up)
        docker compose -f "$COMPOSE_FILE" up -d --build app
        docker compose -f "$COMPOSE_FILE" ps
        ;;
    up-with-queue)
        docker compose -f "$COMPOSE_FILE" --profile queue up -d --build
        docker compose -f "$COMPOSE_FILE" --profile queue ps
        ;;
    down)
        docker compose -f "$COMPOSE_FILE" --profile queue down
        ;;
    logs)
        docker compose -f "$COMPOSE_FILE" logs -f app
        ;;
    ps)
        docker compose -f "$COMPOSE_FILE" --profile queue ps
        ;;
    rebuild)
        docker compose -f "$COMPOSE_FILE" build --no-cache app
        docker compose -f "$COMPOSE_FILE" up -d app
        ;;
    *)
        echo "Usage: $0 {up|up-with-queue|down|logs|ps|rebuild}" >&2
        exit 2
        ;;
esac
