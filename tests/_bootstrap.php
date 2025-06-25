<?php

use craft\test\TestSetup;

ini_set('date.timezone', 'UTC');
date_default_timezone_set('UTC');

define('CRAFT_ROOT_PATH', dirname(__DIR__));

// Use the current installation of Craft
const CRAFT_TESTS_PATH = __DIR__;
!defined('CRAFT_STORAGE_PATH') && define('CRAFT_STORAGE_PATH', __DIR__ . DIRECTORY_SEPARATOR . '_craft' . DIRECTORY_SEPARATOR . 'storage');
!defined('CRAFT_TEMPLATES_PATH') && define('CRAFT_TEMPLATES_PATH', __DIR__ . DIRECTORY_SEPARATOR . '_craft' . DIRECTORY_SEPARATOR . 'templates');
!defined('CRAFT_CONFIG_PATH') && define('CRAFT_CONFIG_PATH', __DIR__ . DIRECTORY_SEPARATOR . '_craft' . DIRECTORY_SEPARATOR . 'config');
!defined('CRAFT_SECRETS_PATH') && define('CRAFT_SECRETS_PATH', CRAFT_CONFIG_PATH . '/secrets.php');
!defined('CRAFT_MIGRATIONS_PATH') && define('CRAFT_MIGRATIONS_PATH', __DIR__ . DIRECTORY_SEPARATOR . '_craft' . DIRECTORY_SEPARATOR . 'migrations');
!defined('CRAFT_TRANSLATIONS_PATH') && define('CRAFT_TRANSLATIONS_PATH', __DIR__ . DIRECTORY_SEPARATOR . '_craft' . DIRECTORY_SEPARATOR . 'translations');
!defined('CRAFT_VENDOR_PATH') && define('CRAFT_VENDOR_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor');

/**
 * Initialize the Laravel Craft Application
 */
(new \Craft\Cms\Tests\TestCase('laravel'))
    ->createApplication();

config()->set('database.default', env('DB_DRIVER'));

$devMode = true;

$compiledTemplates = CRAFT_STORAGE_PATH . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'compiled_classes';
if (is_dir($compiledTemplates)) {
    foreach (new DirectoryIterator($compiledTemplates) as $file) {
        if (!$file->isDot() && $file->getExtension() === 'php') {
            include $compiledTemplates . DIRECTORY_SEPARATOR . $file;
        }
    }
}

TestSetup::configureCraft();
