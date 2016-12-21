<?php

$root = dirname(__DIR__);

// Composer autoloader
require_once $root.'/vendor/autoload.php';

// dotenv
$dotenv = new Dotenv\Dotenv($root);
$dotenv->load();
$dotenv->required(['DB_SERVER', 'DB_USER', 'DB_PASSWORD', 'DB_DATABASE']);

// Craft
define('CRAFT_BASE_PATH', $root.'/craft');
$app = require $root.'/vendor/craftcms/cms/bootstrap/web.php';
$app->run();
