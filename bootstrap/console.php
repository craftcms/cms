<?php

use craft\helpers\Console;

mb_detect_order('auto');

// Normalize how PHP's string methods (strtoupper, etc) behave.
setlocale(
    LC_CTYPE,
    'C.UTF-8', // libc >= 2.13
    'C.utf8', // different spelling
    'en_US.UTF-8', // fallback to lowest common denominator
    'en_US.utf8' // different spelling for fallback
);

// Set default timezone to UTC
date_default_timezone_set('UTC');

$appType = 'console';

/** @var \craft\console\Application $app */
$app = require __DIR__.'/bootstrap.php';

// Make sure we can connect to the DB
if (!$app->getDb()->getIsActive()) {
    Console::stdout("Can't connect to the database with the credentials supplied in db.php. Please double-check them and try again.\n", Console::FG_RED);
    exit(-1);
}

return $app;
