#!/bin/bash

cd "$(dirname "$0")/.."

INIT_SCRIPT_PATH="/app/repos/repo/node_modules/@craftcms/playwright/docker/init-craft.sh"
REPO_PATH="../../../."

DIRNAME=$(basename "$(pwd)")
echo "Current directory: $DIRNAME"
if [ "$DIRNAME" = 'craftcms-playwright' ]
then
  INIT_SCRIPT_PATH="/app/repos/repo/packages/craftcms-playwright/docker/init-craft.sh"
  REPO_PATH="../../."
fi


TESTS_ENV_PATH="$REPO_PATH/tests-playwright/.env"

realpath $TESTS_ENV_PATH
realpath $REPO_PATH

ECHO "TESTS ENV PATH: $TESTS_ENV_PATH"

PLAYWRIGHT_STATUS=$(docker compose --env-file $TESTS_ENV_PATH ps --services --status=running playwright)

# Boot docker container if required
if [ "$PLAYWRIGHT_STATUS" != 'playwright' ]
then
  echo "Building image..."

  BUILD=$REPO_PATH docker compose --env-file $TESTS_ENV_PATH build
  echo "Booting docker…"
  PLAYWRIGHT_REPO_PATH=$REPO_PATH docker compose --env-file $TESTS_ENV_PATH up -d
else
  echo "Container already running, shutting down…"
  docker compose --env-file $TESTS_ENV_PATH down -v
  echo "Booting docker…"
  PLAYWRIGHT_REPO_PATH=$REPO_PATH docker compose --env-file $TESTS_ENV_PATH up -d
fi

# Check if init script has been run
DB_BACKUP_DOESNT_EXIST=$(docker compose --env-file $TESTS_ENV_PATH exec playwright sh -c "ls -la /app/backup | grep 'cannot access'")

if [ "${#DB_BACKUP_DOESNT_EXIST}" ]
then
  echo "Running init scripts…"
  echo $INIT_SCRIPT_PATH
  docker compose --env-file $TESTS_ENV_PATH exec playwright $INIT_SCRIPT_PATH
fi

echo "Container ready!"