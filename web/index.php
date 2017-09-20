<?php
/**
 * Craft web bootstrap file
 */

// Project root path
$root = dirname(__DIR__);

// Composer autoloader
require_once $root.'/vendor/autoload.php';

// dotenv?
if (file_exists($root.'/.env')) {
    $dotenv = new Dotenv\Dotenv($root);
    $dotenv->load();
}

// Craft
define('CRAFT_BASE_PATH', $root);
define('CRAFT_ENVIRONMENT', getenv('CRAFT_ENVIRONMENT') ?: 'production');
$app = require $root.'/vendor/craftcms/cms/bootstrap/web.php';
$app->run();
