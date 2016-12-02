<?php

use craft\helpers\ArrayHelper;

$basePath = dirname(dirname(dirname(__DIR__)));

$srcPath = $basePath.'/src';
$vendorPath = $basePath.'/vendor';

// Load the config
$config = ArrayHelper::merge(
    require $srcPath.'/config/main.php',
    require $srcPath.'/config/common.php',
    require $srcPath.'/config/web.php'
);

$config['vendorPath'] = $vendorPath;

$config = ArrayHelper::merge($config, [
    'components' => [
        'sites' => [
            'currentSite' => 'default'
        ],
        'db' => [
         'class' => '\yii\db\Connection',
         'dsn' => 'mysql:host=localhost;dbname=craft3test',
         'username' => 'root',
         'password' => 'password',
         'charset' => 'utf8',
         'tablePrefix' =>'craft_'
        ]
    ],
]);

return ArrayHelper::merge($config, [
    'class' => craft\web\Application::class,
    'id'=>'craft-test',
    'basePath' => $srcPath
]);
