<?php

use craft\console\Application;
use Symfony\Component\VarDumper\Dumper\CliDumper;
use yii\console\Controller;

return [
    'class' => Application::class,
    'bootstrap' => [
        'queue',
    ],
    'components' => [
        'dumper' => function() {
            $dumper = new CliDumper();
            $dumper->setColors(Craft::$app->controller instanceof Controller && Craft::$app->controller->isColorEnabled());
            return $dumper;
        },
        'errorHandler' => [
            'class' => craft\console\ErrorHandler::class,
        ],
        'request' => [
            'class' => craft\console\Request::class,
            'isConsoleRequest' => true,
        ],
        'user' => [
            'class' => craft\console\User::class,
        ],
    ],
    'controllerMap' => [
        'migrate' => craft\console\controllers\MigrateController::class,
    ],
    'controllerNamespace' => 'craft\\console\\controllers',
];
