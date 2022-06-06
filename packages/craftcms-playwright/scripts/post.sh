#!/bin/bash

cd "$(dirname "$0")/.."

PLAYWRIGHT_STATUS=$(docker compose ps --services --status=running playwright)

# Boot docker container if required
if [ "$PLAYWRIGHT_STATUS" = 'playwright' ]
then
  echo "Container shutting down…"
  docker compose down -v
fi

echo "Shutdown complete."