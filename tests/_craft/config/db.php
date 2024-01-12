<?php

use craft\helpers\App;

return [
    'dsn' => App::env('DB_DSN'),
    'driver' => App::env('DB_DRIVER'),
    'server' => App::env('DB_SERVER'),
    'port' => App::env('DB_PORT'),
    'database' => App::env('DB_DATABASE'),
    'user' => App::env('DB_USER'),
    'password' => App::env('DB_PASSWORD'),
    'schema' => App::env('DB_SCHEMA'),
    'tablePrefix' => App::env('DB_TABLE_PREFIX'),
    'charset' => App::env('DB_CHARSET') ?? 'utf8',
    'collation' => App::env('DB_COLLATION'),
];
