<?php

return [
    'components' => [
        'request' => craft\app\console\Request::class,
        'user' => craft\app\console\User::class,
    ],
    'controllerMap' => [
        'migrate' => craft\app\console\controllers\MigrateController::class,
    ]
];
