<?php

return [
    'password' => getenv('DB_PASSWORD'),
    'user' => getenv('DB_USER'),
    'database' => getenv('DB_DATABASE'),
    'tablePrefix' => getenv('DB_TABLE_PREFIX'),
    'driver' => getenv('DB_DRIVER'),
    'port' => getenv('DB_PORT'),
    'schema' => getenv('DB_SCHEMA'),
    'server' => getenv('DB_SERVER'),
];
