#!/bin/sh

cd /app || exit

su -c "./craft install/craft --interactive=0 --username=admin --password=NewPassword --site-name=Playwright --email=playwright@craftcms.com --site-url=http://127.0.0.1/ --language=en_US" appuser

su -c "sed -i \"s/edition: solo/edition: pro/g\" config/project/project.yaml" appuser

su -c "./craft project-config/apply" appuser

su -c "./craft db/backup db.sql" appuser

su -c 'composer config prefer-stable true && composer config minimum-stability "dev" && composer config repositories.repo path "./repos/*"' appuser

su -c 'composer require $(cat ./repos/repo/composer.json | grep -oE "\"name\": \"([a-zA-Z0-9\/\-]*?)\"" | sed -e "s/\"//g" | sed -e "s/name: //g"):*' appuser

cd /app/repos/repo || exit

su -c 'npx playwright install' appuser