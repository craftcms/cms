<?php

return [
    'driver' => getenv('TEST_DB_DRIVER'),
    'server' => getenv('TEST_DB_SERVER'),
    'user' => getenv('TEST_DB_USER'),
    'password' => getenv('TEST_DB_PASS'),
    'database' => getenv('TEST_DB_NAME'),
    'schema' => getenv('TEST_DB_SCHEMA'),
    'tablePrefix' => getenv('TEST_DB_TABLE_PREFIX'),
    'port' => getenv('TEST_DB_PORT'),
];
