#!/bin/bash

cd "$(dirname "$0")/.."

INIT_SCRIPT_PATH="/var/www/repos/repo/node_modules/@craftcms/playwright/ddev/init-craft.sh"
REPO_PATH="../../../."

DIRNAME=$(basename "$(pwd)")
echo "Current directory: $DIRNAME"
if [ "$DIRNAME" = 'craftcms-playwright' ]
then
  INIT_SCRIPT_PATH="/var/www/repos/repo/packages/craftcms-playwright/ddev/init-craft.sh"
  REPO_PATH="../../."
fi


TESTS_ENV_PATH="$REPO_PATH/tests-playwright/.env"

realpath $TESTS_ENV_PATH
realpath $REPO_PATH

ECHO "TESTS ENV PATH: $TESTS_ENV_PATH"

PLAYWRIGHT_STATUS=$(ddev list --active-only | grep 'playwright')

# Shut down ddev containers if required
if [ "$PLAYWRIGHT_STATUS" != '' ]
then
    echo "Container already running, shutting down…"
    ddev stop
    ddev delete --omit-snapshot --yes
fi

# Boot ddev
echo "Booting docker…"
rm -fr .ddev
cp -r ddev/ddev-config .ddev
cp $TESTS_ENV_PATH .ddev/.env.web
PLAYWRIGHT_REPO_PATH=$REPO_PATH ddev start

# Check if init script has been run
DB_BACKUP_DOESNT_EXIST=$(ddev exec --service web "ls -la /var/www/backup | grep 'cannot access'")

if [ "${#DB_BACKUP_DOESNT_EXIST}" ]
then
  echo "Running init scripts…"
  echo $INIT_SCRIPT_PATH
  ddev exec --service web $INIT_SCRIPT_PATH
fi

echo "Container ready!"