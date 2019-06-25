<?php

define('YII_ENV', 'test');

// Use the current installation of Craft
define('CRAFT_STORAGE_PATH', __DIR__ . DIRECTORY_SEPARATOR . '_craft' . DIRECTORY_SEPARATOR . 'storage');
define('CRAFT_TEMPLATES_PATH', __DIR__ . DIRECTORY_SEPARATOR . '_craft' . DIRECTORY_SEPARATOR . 'templates');
define('CRAFT_CONFIG_PATH', __DIR__ . DIRECTORY_SEPARATOR . '_craft' . DIRECTORY_SEPARATOR . 'config');
define('CRAFT_VENDOR_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor');

$devMode = true;

$vendorPath = realpath(CRAFT_VENDOR_PATH);
$craftPath = __DIR__ . DIRECTORY_SEPARATOR . '_craft';

$configPath = realpath($craftPath . DIRECTORY_SEPARATOR . 'config');
$contentMigrationsPath = realpath($craftPath . DIRECTORY_SEPARATOR . 'migrations');
$storagePath = realpath($craftPath . DIRECTORY_SEPARATOR . 'storage');
$templatesPath = realpath($craftPath . DIRECTORY_SEPARATOR . 'templates');
$translationsPath = realpath($craftPath . DIRECTORY_SEPARATOR . 'translations');

// Log errors to craft/storage/logs/phperrors.log

ini_set('log_errors', 1);
ini_set('error_log', $storagePath . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'phperrors.log');

error_reporting(E_ALL);
ini_set('display_errors', 1);
defined('YII_DEBUG') || define('YII_DEBUG', true);
defined('YII_ENV') || define('YII_ENV', 'dev');
defined('CRAFT_ENVIRONMENT') || define('CRAFT_ENVIRONMENT', '');

defined('CURLOPT_TIMEOUT_MS') || define('CURLOPT_TIMEOUT_MS', 155);
defined('CURLOPT_CONNECTTIMEOUT_MS') || define('CURLOPT_CONNECTTIMEOUT_MS', 156);

// Load the files
$srcPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src';
$libPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'lib';
require $vendorPath . DIRECTORY_SEPARATOR . 'yiisoft' . DIRECTORY_SEPARATOR . 'yii2' . DIRECTORY_SEPARATOR . 'Yii.php';
require $srcPath . DIRECTORY_SEPARATOR . 'Craft.php';

// Set aliases

Craft::setAlias('@lib', $libPath);
Craft::setAlias('@craft', $srcPath);
Craft::setAlias('@config', $configPath);
Craft::setAlias('@contentMigrations', $contentMigrationsPath);
Craft::setAlias('@storage', $storagePath);
Craft::setAlias('@templates', $templatesPath);
Craft::setAlias('@translations', $translationsPath);
