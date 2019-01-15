<?php

/**
 * Database Configuration
 *
 * All of your system's database configuration settings go in here.
 * You can see a list of the default settings in craft/app/config/defaults/db.php
 */

return [

    // The database server name or IP address. Usually this is 'localhost' or '127.0.0.1'.
    'server' => 'localhost',

    // The database username to connect with.
    'user' => getenv('TEST_DB_USER'),

    // The database password to connect with.
    'password' => getenv('TEST_DB_PASS'),

    // The name of the database to select.
    'database' => getenv('TEST_DB_NAME'),

    // The prefix to use when naming tables. This can be no more than 5 characters.
    'tablePrefix' => 'craft',

];
