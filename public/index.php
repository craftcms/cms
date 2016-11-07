<?php

defined('CRAFT_BASE_PATH') || define('CRAFT_BASE_PATH', dirname(__DIR__).'/craft');
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/vendor/craftcms/craft/bootstrap/web.php';
$app->run();
