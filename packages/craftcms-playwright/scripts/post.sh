#!/bin/bash

cd "$(dirname "$0")/.."

REPO_PATH="../../../."

DIRNAME=$(basename "$(pwd)")
echo "Current directory: $DIRNAME"
if [ "$DIRNAME" = 'craftcms-playwright' ]
then
  REPO_PATH="../../."
fi

TESTS_ENV_PATH="$REPO_PATH/tests-playwright/.env"

PLAYWRIGHT_STATUS=$(docker compose --env-file $TESTS_ENV_PATH ps --services --status=running playwright)

# Boot docker container if required
if [ "$PLAYWRIGHT_STATUS" = 'playwright' ]
then
  echo "Container shutting down…"
  docker compose --env-file $TESTS_ENV_PATH down -v
fi

echo "Shutdown complete."