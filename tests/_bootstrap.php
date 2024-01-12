<?php

use craft\test\TestSetup;

ini_set('date.timezone', 'UTC');
date_default_timezone_set('UTC');

// Use the current installation of Craft
const CRAFT_TESTS_PATH = __DIR__;
const CRAFT_STORAGE_PATH = __DIR__ . DIRECTORY_SEPARATOR . '_craft' . DIRECTORY_SEPARATOR . 'storage';
const CRAFT_TEMPLATES_PATH = __DIR__ . DIRECTORY_SEPARATOR . '_craft' . DIRECTORY_SEPARATOR . 'templates';
const CRAFT_CONFIG_PATH = __DIR__ . DIRECTORY_SEPARATOR . '_craft' . DIRECTORY_SEPARATOR . 'config';
const CRAFT_SECRETS_PATH = CRAFT_CONFIG_PATH . '/secrets.php';
const CRAFT_MIGRATIONS_PATH = __DIR__ . DIRECTORY_SEPARATOR . '_craft' . DIRECTORY_SEPARATOR . 'migrations';
const CRAFT_TRANSLATIONS_PATH = __DIR__ . DIRECTORY_SEPARATOR . '_craft' . DIRECTORY_SEPARATOR . 'translations';
define('CRAFT_VENDOR_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor');

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
