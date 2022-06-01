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


PLAYWRIGHT_STATUS=$(docker compose ps --services --status=running playwright)

# Boot docker container if required
if [ "$PLAYWRIGHT_STATUS" != 'playwright' ]
then
  echo "Booting docker"
  PLAYWRIGHT_REPO_PATH=$REPO_PATH docker compose up -d
else
  docker compose down -v
  PLAYWRIGHT_REPO_PATH=$REPO_PATH docker compose up -d
fi

# Check if init script has been run
DB_BACKUP_DOESNT_EXIST=$(docker compose exec playwright sh -c "ls -la /app/backup | grep 'cannot access'")

if [ "$DB_BACKUP_DOESNT_EXIST" ]
then
  echo "Running init"
  docker compose exec playwright $INIT_SCRIPT_PATH
fi

echo 'ready.'
