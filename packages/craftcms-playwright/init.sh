#!/bin/bash

# Add safe directory to allow commands later in the process
su -c 'git config --global --add safe.directory /app/repos/repo' appuser

# Copy PHP files from the package to the Craft project install
su -c 'cp -vfrp /app/repos/repo/node_modules/@craftcms/playwright/php/DbBackup.php /app/modules/.' appuser
su -c 'cp -vfrp /app/repos/repo/node_modules/@craftcms/playwright/php/app.php /app/config/.' appuser

# Switch to app directory
cd /app || exit

# Install Craft
su -c "./craft install/craft --interactive=0 --username=admin --password=NewPassword --site-name=Playwright --email=playwright@craftcms.com --site-url=http://127.0.0.1/ --language=en_US" appuser

# Switch Craft's edition and apply changes
su -c "sed -i \"s/edition: solo/edition: pro/g\" config/project/project.yaml" appuser
su -c "./craft project-config/apply" appuser

# Create backup directory
su -c "mkdir backup" appuser

# Backup DB
su -c "./craft db/backup backup/db.sql" appuser

# Backup Project Config files
su -c "cp -vfrp ./config/project ./backup/." appuser

# Switch to repo directory
cd /app/repos/repo || exit

# Get the latest tagged version from the repo
REPO_VERSION=$(su -c 'git describe --tags --abbrev=0' appuser)

# Switch to app directory
cd /app || exit

# Get the package name for the repo that is being worked on
PACKAGE_NAME=$(cat ./repos/repo/composer.json | grep -oE "\"name\": \"([a-zA-Z0-9\/\-]*?)\"" | sed -e "s/\"//g" | sed -e "s/name: //g")

# Create composer CLI command to add the `repositories` key for symlinking
REPOSITORIES_CMD="composer config repositories.repo '{\"type\": \"path\", \"url\": \"./repos/*\", \"options\": {\"versions\": {\""$PACKAGE_NAME"\": \""$REPO_VERSION"\"}}}'"

# Set config items in `composer.json`
composer config prefer-stable true && composer config minimum-stability "dev"

# Run repositories command
eval "$REPOSITORIES_CMD"

# Composer require the current repo that is being worked on to create the symlink
su -c "composer require $PACKAGE_NAME:*" appuser

# Backup composer files
su -c "cp -vfrp composer.* ./backup/." appuser