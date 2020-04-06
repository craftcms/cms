<?php

return [
    'password' => Craft::getEnv('DB_PASSWORD'),
    'user' => Craft::getEnv('DB_USER'),
    'database' => Craft::getEnv('DB_DATABASE'),
    'tablePrefix' => Craft::getEnv('DB_TABLE_PREFIX'),
    'driver' => Craft::getEnv('DB_DRIVER'),
    'port' => Craft::getEnv('DB_PORT'),
    'schema' => Craft::getEnv('DB_SCHEMA'),
    'server' => Craft::getEnv('DB_SERVER'),
];
