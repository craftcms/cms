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

REPO_PATH="../../../."

DIRNAME=$(basename "$(pwd)")

if [ "$DIRNAME" = 'craftcms-playwright' ]
then
  REPO_PATH="../../."
fi

TESTS_ENV_PATH="$REPO_PATH/tests-playwright/.env"

PLAYWRIGHT_STATUS=$(ddev list --active-only | grep 'playwright')

# Boot docker container if required
if [ "$PLAYWRIGHT_STATUS" != '' ]
then
  ddev stop
  ddev delete --omit-snapshot --yes
fi

echo -e "${LOGGER_PREFIX}Shutdown complete."