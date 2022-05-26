#!/bin/bash

# Switch to app directory
cd /app || exit

# Install Craft
su -c "./craft install/craft --interactive=0 --username=admin --password=NewPassword --site-name=Playwright --email=playwright@craftcms.com --site-url=http://127.0.0.1/ --language=en_US" appuser

# Switch Craft's edition and apply changes
su -c "sed -i \"s/edition: solo/edition: pro/g\" config/project/project.yaml" appuser

su -c "./craft project-config/apply" appuser

su -c "mkdir backup" appuser

# Backup DB
su -c "./craft db/backup backup/db.sql" appuser

# Backup Project Config files
su -c "cp -vfrp ./config/project ./backup/." appuser

cd /app/repos/repo || exit

REPO_VERSION=$(su -c 'git describe --tags --abbrev=0' appuser)

cd /app || exit

PACKAGE_NAME=$(cat ./repos/repo/composer.json | grep -oE "\"name\": \"([a-zA-Z0-9\/\-]*?)\"" | sed -e "s/\"//g" | sed -e "s/name: //g")

REPOSITORIES_CMD="composer config repositories.repo '{\"type\": \"path\", \"url\": \"./repos/*\", \"options\": {\"versions\": {\""$PACKAGE_NAME"\": \""$REPO_VERSION"\"}}}'"

# Set config items in `composer.json`
composer config prefer-stable true && composer config minimum-stability "dev"

eval "$REPOSITORIES_CMD"

# Composer require the current repo that is being worked on to create the symlink
su -c "composer require $PACKAGE_NAME:*" appuser

# Backup composer files
su -c "cp -vfrp composer.* ./backup/." appuser

# Switch to repo directory
cd /app/repos/repo || exit

# Install playwright browsers
su -c 'npx playwright install' appuser