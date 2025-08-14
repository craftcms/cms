#!/bin/bash

# Add safe directory to allow commands later in the process
git config --global --add safe.directory /app/repos/repo

# Switch to /app directory
cd /app || exit

echo "$PWD"

# Install Craft
echo "install craft"
./craft install/craft --interactive=0 --username=admin --password=NewPassword --site-name=Playwright --email=playwright@craftcms.com --site-url=http://127.0.0.1/ --language=en_US

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

# autoload modules via composer
sed -i "s/\"prefer-stable\": true,/\"prefer-stable\": true,\n  \"autoload\": {\"psr-4\": {\"modules\\\\\\\\\": \"modules\/\"}},/g" composer.json
composer dump-autoload

cp -vfrp /app/repos/repo/node_modules/@craftcms/playwright/php/DbBackup.php /app/modules/

if [ ! -d "modules/seeder" ]
then
  echo "creating modules/seeder dir"
  mkdir modules/seeder
fi

cp -vfrp /app/repos/repo/tests/fixtures /app/modules/seeder/
rm -fr /app/modules/seeder/fixtures/data
sed -i "s/crafttests\\\\fixtures/modules\\\\seeder\\\\fixtures/g" /app/modules/seeder/fixtures/*Fixture.php
cp -vfrp /app/repos/repo/node_modules/@craftcms/playwright/fixtures/data /app/modules/seeder/fixtures/

cp -vfrp /app/repos/repo/node_modules/@craftcms/playwright/php/app.php /app/config/


# Create backup directory
mkdir backup

# Backup DB
echo "backup DB"
./craft db/backup --interactive=0 --overwrite=1 backup/db.sql

# Backup Project Config files
echo "backup Project Config files"
cp -vfrp config/project backup/

# Switch to repo directory
cd /app/repos/repo || exit

# Get the latest tagged version from the repo
REPO_VERSION=$(git describe --tags --abbrev=0)

echo "$REPO_VERSION"

# Switch to app directory
cd /app || exit

# Get the package name for the repo that is being worked on
PACKAGE_NAME=$(cat /app/repos/repo/composer.json | grep -oE "\"name\": \"([a-zA-Z0-9\/\-]*?)\"" | sed -e "s/\"//g" | sed -e "s/name: //g")

echo "$PACKAGE_NAME"
## Create composer CLI command to add the `repositories` key for symlinking
REPOSITORIES_CMD="composer config repositories.repo '{\"type\": \"path\", \"url\": \"/app/repos/*\", \"options\": {\"versions\": {\""$PACKAGE_NAME"\": \""$REPO_VERSION"\"}}}'"

# Set config items in `composer.json`
composer config prefer-stable true && composer config minimum-stability "dev"

# Run repositories command
eval "$REPOSITORIES_CMD"

# Composer require the current repo that is being worked on to create the symlink
composer require $PACKAGE_NAME:*

## Backup composer files
echo "backup composer files"
cp -vfrp composer.* /app/backup/