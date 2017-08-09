<?php
/**
 * Database Configuration
 *
 * All of your system's database connection settings go in here. You can see a
 * list of the available settings in vendor/craftcms/cms/src/config/DbConfig.php.
 */

return [
    'driver' => getenv('CRAFT_DB_DRIVER'),
    'server' => getenv('CRAFT_DB_SERVER'),
    'user' => getenv('CRAFT_DB_USER'),
    'password' => getenv('CRAFT_DB_PASSWORD'),
    'database' => getenv('CRAFT_DB_DATABASE'),
    'schema' => getenv('CRAFT_DB_SCHEMA'),
    'tablePrefix' => getenv('CRAFT_DB_TABLE_PREFIX'),
    'port' => getenv('CRAFT_DB_PORT')
];
