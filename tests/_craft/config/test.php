<?php

use craft\helpers\ArrayHelper;
use craft\services\Config;

$_SERVER['REMOTE_ADDR'] = '1.1.1.1';
$_SERVER['REMOTE_PORT'] = 654321;

$basePath = dirname(dirname(dirname(__DIR__)));

$srcPath = $basePath . '/src';
$vendorPath = $basePath . '/vendor';

Craft::setAlias('@craftunitsupport', $srcPath.'/test');
Craft::setAlias('@craftunittemplates', $basePath.'/tests/_craft/templates');
Craft::setAlias('@craftunitfixtures', $basePath.'/tests/fixtures');
Craft::setAlias('@craft', $srcPath);
Craft::setAlias('@testsfolder', $basePath.'/tests');
Craft::setAlias('@crafttestsfolder', $basePath.'/tests/_craft');
Craft::setAlias('@vendor', $basePath.'/vendor');

$customConfig = \craft\test\Craft::getTestSetupConfig();

// Load the config
$config = ArrayHelper::merge(
    [
        'components' => [
            'config' => [
                'class' => Config::class,
                'configDir' => __DIR__,
                'appDefaultsDir' => $srcPath . '/config/defaults',
            ],
        ],
    ],
    require $srcPath . '/config/app.php',
    require $srcPath . '/config/app.web.php'
);

if (is_array($customConfig)) {
    // Merge in any custom variables and config
    $config = ArrayHelper::merge($config, $customConfig);
}

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
    'id' => 'craft-test',
    'basePath' => $srcPath
]);
