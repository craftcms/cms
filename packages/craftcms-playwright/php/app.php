<?php

/**
 * Yii Application Config
 *
 * Edit this file at your own risk!
 *
 * The array returned by this file will get merged with
 * vendor/craftcms/cms/src/config/app.php and app.[web|console].php, when
 * Craft's bootstrap script is defining the configuration for the entire
 * application.
 *
 * You can define custom modules and system components, and even override the
 * built-in system components.
 *
 * If you want to modify the application config for *only* web requests or
 * *only* console requests, create an app.web.php or app.console.php file in
 * your config/ folder, alongside this one.
 */

use craft\helpers\App;
use craft\helpers\StringHelper;

return [
    'id' => App::env('CRAFT_APP_ID') ?: 'CraftCMS',
    'aliases' => [
        '@'.str_replace('\\', '/', App::env('CODECEPTION_FIXTURES_NAMESPACE')) => '/var/www/repos/repo'.StringHelper::ensureLeft(App::env('CODECEPTION_FIXTURES_PATH'), '/'),
    ],
    'modules' => [
        'db-backup' => \modules\DbBackup::class,
    ],
    'bootstrap' => ['db-backup'],
];
