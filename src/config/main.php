<?php

$config = [
    'id' => 'CraftCMS',
    'name' => 'Craft CMS',
    'version' => '3.0',
    'build' => '@@@build@@@',
    'schemaVersion' => '3.0.15',
    'releaseDate' => '@@@releaseDate@@@',
    'minBuildRequired' => '@@@minBuildRequired@@@',
    'minBuildUrl' => '@@@minBuildUrl@@@',
    'track' => '@@@track@@@',
    'basePath' => '@craft/app',          // Defines the @app alias
    'runtimePath' => '@storage/runtime', // Defines the @runtime alias
    'controllerNamespace' => 'craft\app\controllers',
];

/* HIDE */
// Default version/build values for running Craft directly from the source
$config['build'] = '1';
$config['releaseDate'] = '1429092000';
$config['minBuildRequired'] = '0';
$config['minBuildUrl'] = 'http://craftcms.com/';
$config['track'] = 'stable';
/* end HIDE */
return $config;
