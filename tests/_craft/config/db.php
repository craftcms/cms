<?php

return [
    'password' => \craft\test\Craft::getEnv('DB_PASSWORD'),
    'user' => \craft\test\Craft::getEnv('DB_USER'),
    'database' => \craft\test\Craft::getEnv('DB_DATABASE'),
    'tablePrefix' => \craft\test\Craft::getEnv('DB_TABLE_PREFIX'),
    'driver' => \craft\test\Craft::getEnv('DB_DRIVER'),
    'port' => \craft\test\Craft::getEnv('DB_PORT'),
    'schema' => \craft\test\Craft::getEnv('DB_SCHEMA'),
    'server' => \craft\test\Craft::getEnv('DB_SERVER'),
];
