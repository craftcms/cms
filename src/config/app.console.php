<?php

return [
    'class' => \craft\console\Application::class,
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
