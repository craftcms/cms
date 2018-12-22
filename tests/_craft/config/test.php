<?php

use craft\helpers\ArrayHelper;
use craft\services\Config;

$_SERVER['REMOTE_ADDR'] = '1.1.1.1';
$_SERVER['REMOTE_PORT'] = 654321;

$basePath = dirname(dirname(dirname(__DIR__)));

$srcPath = $basePath.'/src';
$vendorPath = $basePath.'/vendor';

// Load the config
$config = ArrayHelper::merge(
    [
        'components' => [
            'config' => [
                'class' => Config::class,
                'configDir' => __DIR__,
                'appDefaultsDir' => $srcPath.'/config/defaults',
            ],
        ],
    ],
    require $srcPath.'/config/app.php',
    require $srcPath.'/config/app.web.php'
);

$config['vendorPath'] = $vendorPath;

$config = ArrayHelper::merge($config, [
    'components' => [
        'sites' => [
            'currentSite' => 'default'
        ]
    ],
]);

return ArrayHelper::merge($config, [
    'class' => craft\web\Application::class,
    'id'=>'craft-test',
    'basePath' => $srcPath
]);
