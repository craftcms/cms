<?php

use CraftCms\Cms\Support\Env;

return [
    'dsn' => Env::get('CRAFT_DB_DSN'),
    'user' => Env::get('CRAFT_DB_USER'),
    'password' => Env::get('CRAFT_DB_PASSWORD'),
    'schema' => Env::get('CRAFT_DB_SCHEMA'),
    'tablePrefix' => Env::get('CRAFT_DB_TABLE_PREFIX'),
];
