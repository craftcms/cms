<?php

defined('CRAFT_VENDOR_PATH') || define('CRAFT_VENDOR_PATH', __DIR__.'/vendor');
defined('CRAFT_BASE_PATH') || define('CRAFT_BASE_PATH', dirname(__DIR__));

$app = require __DIR__.'/vendor/craftcms/craft/bootstrap/web.php';
$app->run();
