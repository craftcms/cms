<?php

use craft\console\Application;

return [
    'class' => Application::class,
    'bootstrap' => [
        'queue',
    ],
    'components' => [
        'request' => craft\console\Request::class,
        'user' => craft\console\User::class,
    ],
    'controllerMap' => [
        'migrate' => craft\console\controllers\MigrateController::class,
    ],
    'controllerNamespace' => 'craft\\console\\controllers',
];
