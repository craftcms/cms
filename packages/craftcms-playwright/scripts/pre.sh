#!/bin/bash

cd "$(dirname "$0")/.."

INIT_SCRIPT_PATH="/app/repos/repo/node_modules/@craftcms/playwright/scripts/init-craft.sh"
REPO_PATH="../../../."
DIRNAME=$(basename "$(pwd)")
if [ "$DIRNAME" = 'craftcms-playwright' ]
then
  INIT_SCRIPT_PATH="/app/repos/repo/packages/craftcms-playwright/scripts/init-craft.sh"
  REPO_PATH="../../."
fi


PLAYWRIGHT_STATUS=$(docker compose --env-file ../../tests-playwright/.env ps --services --status=running playwright)

# Boot docker container if required
if [ "$PLAYWRIGHT_STATUS" != 'playwright' ]
then
  echo "Building image..."
  echo "$REPO_PATH"
  ls -la $REPO_PATH
  BUILD=$REPO_PATH docker compose --env-file ../../tests-playwright/.env build
  echo "Booting docker…"
  PLAYWRIGHT_REPO_PATH=$REPO_PATH docker compose --env-file ../../tests-playwright/.env up -d
else
  echo "Container already running, shutting down…"
  docker compose --env-file ../../tests-playwright/.env down -v
  echo "Booting docker…"
  PLAYWRIGHT_REPO_PATH=$REPO_PATH docker compose --env-file ../../tests-playwright/.env up -d
fi

# Check if init script has been run
DB_BACKUP_DOESNT_EXIST=$(docker compose --env-file ../../tests-playwright/.env exec playwright sh -c "ls -la /app/backup | grep 'cannot access'")

if [ "${#DB_BACKUP_DOESNT_EXIST}" ]
then
  echo "Running init scripts…"
  echo $INIT_SCRIPT_PATH
  docker compose --env-file ../../tests-playwright/.env exec playwright $INIT_SCRIPT_PATH
fi

echo "Container ready!"