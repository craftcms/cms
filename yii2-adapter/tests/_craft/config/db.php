<?php

use CraftCms\Cms\Support\Env;

return [
    'dsn' => Env::get('DB_DSN'),
    'driver' => Env::get('DB_CONNECTION'),
    'server' => Env::get('DB_HOST'),
    'port' => Env::get('DB_PORT'),
    'database' => Env::get('DB_DATABASE'),
    'user' => Env::get('DB_USERNAME'),
    'password' => Env::get('DB_PASSWORD'),
    'schema' => Env::get('DB_SCHEMA'),
    'tablePrefix' => Env::get('DB_TABLE_PREFIX'),
    'charset' => Env::get('DB_CHARSET') ?? 'utf8',
    'collation' => Env::get('DB_COLLATION'),
];
