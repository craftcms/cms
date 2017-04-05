<?php

use craft\helpers\Console;

$appType = 'console';

/** @var \craft\console\Application $app */
$app = require __DIR__.'/bootstrap.php';

// Make sure we can connect to the DB
if (!$app->getDb()->getIsActive()) {
    Console::stdout("Can't connect to the database with the credentials supplied in db.php. Please double-check them and try again.\n", Console::FG_RED);
    exit(-1);
}

return $app;
