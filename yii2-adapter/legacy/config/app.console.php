<?php

use craft\console\Application;
use craft\console\ErrorHandler;
use craft\console\Request;
use craft\console\User;
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
            'class' => ErrorHandler::class,
        ],
        'request' => [
            'class' => Request::class,
            'isConsoleRequest' => true,
        ],
        'user' => [
            'class' => User::class,
        ],
    ],
    'controllerNamespace' => 'craft\\console\\controllers',
];
