#!/bin/bash

# Define color codes
GRAY="\033[90m"
GREEN="\033[32m"
UNDERLINE="\033[4m"
RESET="\033[0m"
BLUE="\033[34m"
YELLOW="\033[33m"

LOGGER_PREFIX="${GRAY}[Craft Playwright] › ${GREEN}✔  ${UNDERLINE}success${RESET}   "
LOGGER_START_PREFIX="${GRAY}[Craft Playwright] › ${GREEN}▶  ${UNDERLINE}start${RESET}   "
LOGGER_NOTE_PREFIX="${GRAY}[Craft Playwright] › ${BLUE}●  ${UNDERLINE}info${RESET}   "
LOGGER_WARNING_PREFIX="${GRAY}[Craft Playwright] › ${YELLOW}▲  ${UNDERLINE}warning${RESET}   "

cd "$(dirname "$0")/.."

INIT_SCRIPT_PATH="/var/www/repos/repo/node_modules/@craftcms/playwright/ddev/init-craft.sh"
REPO_PATH="../../../."

DIRNAME=$(basename "$(pwd)")
echo -e "${LOGGER_NOTE_PREFIX}Current directory: $DIRNAME"
if [ "$DIRNAME" = 'craftcms-playwright' ]
then
  INIT_SCRIPT_PATH="/var/www/repos/repo/packages/craftcms-playwright/ddev/init-craft.sh"
  REPO_PATH="../../."
fi

TESTS_PATH="$REPO_PATH/tests-playwright"

echo -e "${LOGGER_NOTE_PREFIX}Path to Playwright tests: $TESTS_PATH"

PLAYWRIGHT_STATUS=$(ddev list --active-only | grep 'playwright')

# Shut down ddev containers if required
if [ "$PLAYWRIGHT_STATUS" != '' ]
then
    echo -e "${LOGGER_WARNING_PREFIX}Container already running, shutting down…"
    ddev stop
    ddev delete --omit-snapshot --yes
    echo -e "${LOGGER_PREFIX}Container shutdown."
fi

# prep to boot ddev - copy configs
rm -fr .ddev
cp -r ddev/ddev-config .ddev
cp "$TESTS_PATH/.env" .ddev/.env.web

# copy custom configs
if [ -d "$TESTS_PATH/ddev-config" ]
then
  cp -r "$TESTS_PATH/ddev-config/." .ddev/
fi

echo -e "${LOGGER_START_PREFIX}Starting DDEV…"
PLAYWRIGHT_REPO_PATH=$REPO_PATH ddev start

# Check if init script has been run
DB_BACKUP_DOESNT_EXIST=$(ddev exec --service web "ls -la /var/www/backup | grep 'cannot access'")

if [ "${#DB_BACKUP_DOESNT_EXIST}" ]
then
  ddev exec --service web $INIT_SCRIPT_PATH
  echo -e "${LOGGER_PREFIX}Container initialized."
fi

echo -e "${LOGGER_PREFIX}Container ready."