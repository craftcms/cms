#!/bin/bash

# Add safe directory to allow commands later in the process
git config --global --add safe.directory /var/www/repos/repo

# Switch to /var/www/html directory
cd /var/www/html || exit

# Install Craft
echo "install craft"
./craft install/craft --interactive=0 --username=admin --password=NewPassword --site-name=Playwright --email=playwright@craftcms.com --site-url=https://playwright.ddev.site/ --language=en_US

# Switch Craft's edition and apply changes
sed -i "s/edition: solo/edition: pro/g" config/project/project.yaml
./craft project-config/apply

if [ ! -d "modules" ]
then
  echo "creating modules dir"
  mkdir modules
fi

if [ ! -d "config" ]
then
  echo "creating config dir"
  mkdir config
fi

# autoload modules and fixtures via composer
FIXTURES_NAMESPACE=${CODECEPTION_FIXTURES_NAMESPACE//\\/\\\\}
FIXTURES_PATH="\/var\/www\/repos\/repo\/"${CODECEPTION_FIXTURES_PATH//\//\\/}

sed -i "s/\"prefer-stable\": true,/\"prefer-stable\": true,\n  \"autoload\": {\"psr-4\": {\"${FIXTURES_NAMESPACE//\\/\\\\}\\\\\\\\\": \"${FIXTURES_PATH}\",\"modules\\\\\\\\\": \"modules\/\"}},/g" composer.json
composer dump-autoload

cp -vfrp /var/www/repos/repo/node_modules/@craftcms/playwright/php/DbBackup.php /var/www/html/modules/

cp -vfrp /var/www/repos/repo/node_modules/@craftcms/playwright/php/app.php /var/www/html/config/


# Create backup
echo "create backup dir"
mkdir /var/www/backup

# Backup DB
echo "backup DB"
./craft db/backup --interactive=0 --overwrite=1 /var/www/backup/db.sql

# Backup Project Config files
echo "backup Project Config files"
cp -vfrp config/project /var/www/backup/

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
echo "backup composer files"
cp -vfrp composer.* /var/www/backup/