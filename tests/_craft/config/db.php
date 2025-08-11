<?php

use CraftCms\Cms\Support\Env;

return [
    'dsn' => Env::get('DB_DSN'),
    'driver' => Env::get('DB_DRIVER'),
    'server' => Env::get('DB_SERVER'),
    'port' => Env::get('DB_PORT'),
    'database' => Env::get('DB_DATABASE'),
    'user' => Env::get('DB_USER'),
    'password' => Env::get('DB_PASSWORD'),
    'schema' => Env::get('DB_SCHEMA'),
    'tablePrefix' => Env::get('DB_TABLE_PREFIX'),
    'charset' => Env::get('DB_CHARSET') ?? 'utf8',
    'collation' => Env::get('DB_COLLATION'),
];
