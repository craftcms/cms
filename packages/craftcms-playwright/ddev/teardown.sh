#!/bin/bash

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
  echo "Container shutting down…"
  ddev stop
  ddev delete --omit-snapshot --yes
fi

echo "Shutdown complete."