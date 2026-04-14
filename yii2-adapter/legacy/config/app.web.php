<?php

use craft\helpers\App;
use craft\web\Application;
use craft\web\ErrorHandler;
use craft\web\Request;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

return [
    'class' => Application::class,
    'components' => [
        'assetManager' => function() {
            $config = App::assetManagerConfig();
            return Craft::createObject($config);
        },
        'dumper' => function() {
            $dumper = new HtmlDumper();
            $dumper->setTheme('light');
            return $dumper;
        },
        'request' => function() {
            $config = App::webRequestConfig();
            /** @var Request $request */
            $request = Craft::createObject($config);
            $request->csrfCookie = Craft::cookieConfig([], $request);
            return $request;
        },
        'response' => function() {
            $config = App::webResponseConfig();
            return Craft::createObject($config);
        },
        'session' => function() {
            $config = App::sessionConfig();
            return Craft::createObject($config);
        },
        'user' => function() {
            $config = App::userConfig();
            return Craft::createObject($config);
        },
        'errorHandler' => [
            'class' => ErrorHandler::class,
        ],
    ],
    'controllerNamespace' => 'craft\\controllers',
];
