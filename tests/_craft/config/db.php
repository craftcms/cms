<?php

/**
 * Database Configuration
 *
 * All of your system's database configuration settings go in here.
 * You can see a list of the default settings in craft/app/config/defaults/db.php
 */

return [

	// The database server name or IP address. Usually this is 'localhost' or '127.0.0.1'.
    'driver' => getenv('TEST_DB_DRIVER'),
    'server' => getenv('TEST_DB_SERVER'),
	'user' => getenv('TEST_DB_USER'),
    'password' => getenv('TEST_DB_PASS'),
    'database' => getenv('TEST_DB_NAME'),
    'schema' => getenv('TEST_DB_SCHEMA'),
    'tablePrefix' => getenv('TEST_DB_TABLE_PREFIX'),
    'port' => getenv('TEST_DB_PORT'),

];
