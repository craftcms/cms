<?php

$config = [
    'id' => 'Craft',
    'name' => 'Craft',
    'version' => '@@@version@@@',
    'build' => '@@@build@@@',
    'schemaVersion' => '@@@schemaVersion@@@',
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
$config['version'] = '3.0';
$config['build'] = '1';
$config['schemaVersion'] = '3.0.3';
$config['releaseDate'] = '1429092000';
$config['minBuildRequired'] = '0';
$config['minBuildUrl'] = 'http://buildwithcraft.com/';
$config['track'] = 'stable';
/* end HIDE */
return $config;
