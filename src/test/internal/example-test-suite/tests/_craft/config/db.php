<?php

use craft\helpers\App;

return [
    'dsn' => App::env('DB_DSN'),
    'user' => App::env('DB_USER'),
    'password' => App::env('DB_PASSWORD'),
    'schema' => App::env('DB_SCHEMA'),
    'tablePrefix' => App::env('DB_TABLE_PREFIX'),
];
