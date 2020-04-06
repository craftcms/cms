<?php

return [
    'dsn' => Craft::getEnv('DB_DSN'),
    'user' => Craft::getEnv('DB_USER'),
    'password' => Craft::getEnv('DB_PASSWORD'),
    'schema' => Craft::getEnv('DB_SCHEMA'),
    'tablePrefix' => Craft::getEnv('DB_TABLE_PREFIX'),
];
