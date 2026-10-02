#!/bin/bash

# Define color codes
GRAY="\033[90m"
GREEN="\033[32m"
UNDERLINE="\033[4m"
RESET="\033[0m"

LOGGER_PREFIX="${GRAY}[Craft Playwright] › ${GREEN}✔  ${UNDERLINE}success${RESET}   "

# Add safe directory to allow commands later in the process
git config --global --add safe.directory /var/www/repos/repo

# Switch to /var/www/html directory
cd /var/www/html || exit

# Install Craft
./craft install/craft --interactive=0 --username="$AUTH_USERNAME" --password="$AUTH_PASSWORD" --site-name=Playwright --email=playwright@craftcms.com --site-url="$PRIMARY_SITE_URL" --language=en_US
echo -e "${LOGGER_PREFIX}Craft installed."

# Switch Craft's edition and apply changes
sed -i "s/edition: solo/edition: pro/g" config/project/project.yaml
./craft project-config/apply

if [ ! -d "modules" ]
then
  mkdir modules
  echo -e "${LOGGER_PREFIX}Modules directory created."
fi

if [ ! -d "config" ]
then
  mkdir config
  echo -e "${LOGGER_PREFIX}Config directory created."
fi

# autoload modules and fixtures via composer
FIXTURES_NAMESPACE=${CODECEPTION_FIXTURES_NAMESPACE//\\/\\\\}
FIXTURES_PATH="\/var\/www\/repos\/repo\/"${CODECEPTION_FIXTURES_PATH//\//\\/}

sed -i "s/\"prefer-stable\": true,/\"prefer-stable\": true,\n  \"autoload\": {\"psr-4\": {\"${FIXTURES_NAMESPACE//\\/\\\\}\\\\\\\\\": \"${FIXTURES_PATH}\",\"modules\\\\\\\\\": \"modules\/\"}},/g" composer.json
composer dump-autoload

cp -vfrp /var/www/repos/repo/node_modules/@craftcms/playwright/php/DbBackup.php /var/www/html/modules/

cp -vfrp /var/www/repos/repo/node_modules/@craftcms/playwright/php/app.php /var/www/html/config/


# Create backup
mkdir /var/www/backup
echo -e "${LOGGER_PREFIX}Backup directory created."

# Backup DB
./craft db/backup --interactive=0 --overwrite=1 /var/www/backup/db.sql
echo -e "${LOGGER_PREFIX}Database backup created."

# Backup Project Config files
cp -vfrp config/project /var/www/backup/
echo -e "${LOGGER_PREFIX}Project config files backed up."

# Switch to repo directory
cd /var/www/repos/repo || exit

# Get the latest tagged version from the repo
REPO_VERSION=$(git describe --tags --abbrev=0)

# Switch to app directory
cd /var/www/html || exit

# Get the package name for the repo that is being worked on
PACKAGE_NAME=$(cat /var/www/repos/repo/composer.json | grep -oE "\"name\": \"([a-zA-Z0-9\/\-]*?)\"" | sed -e "s/\"//g" | sed -e "s/name: //g")

## Create composer CLI command to add the `repositories` key for symlinking
REPOSITORIES_CMD="composer config repositories.repo '{\"type\": \"path\", \"url\": \"/var/www/repos/*\", \"options\": {\"versions\": {\""$PACKAGE_NAME"\": \""$REPO_VERSION"\"}}}'"

# Set config items in `composer.json`
composer config prefer-stable true && composer config minimum-stability "dev"

# Run repositories command
eval "$REPOSITORIES_CMD"

rm -rf composer.lock

# Composer require the current repo that is being worked on to create the symlink
composer require $PACKAGE_NAME:*

## Backup composer files
cp -vfrp composer.* /var/www/backup/
echo -e "${LOGGER_PREFIX}Composer files backed up."