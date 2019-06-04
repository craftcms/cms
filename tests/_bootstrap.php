<?php

use craft\test\TestSetup;

ini_set('date.timezone', 'UTC');

// Use the current installation of Craft
define('CRAFT_STORAGE_PATH', __DIR__ . '/_craft/storage');
define('CRAFT_TEMPLATES_PATH', __DIR__ . '/_craft/templates');
define('CRAFT_CONFIG_PATH', __DIR__ . '/_craft/config');
define('CRAFT_MIGRATIONS_PATH', __DIR__ . '/_craft/migrations');
define('CRAFT_TRANSLATIONS_PATH', __DIR__ . '/_craft/translations');
define('CRAFT_VENDOR_PATH', dirname(__DIR__) . '/vendor');

$devMode = true;

TestSetup::configureCraft();
