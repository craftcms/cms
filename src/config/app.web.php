<?php

use Symfony\Component\VarDumper\Dumper\HtmlDumper;

return [
    'class' => craft\web\Application::class,
    'components' => [
        'assetManager' => function() {
            $config = craft\helpers\App::assetManagerConfig();
            return Craft::createObject($config);
        },
        'dumper' => function() {
            $dumper = new HtmlDumper();
            $dumper->setTheme('light');
            return $dumper;
        },
        'request' => function() {
            $config = craft\helpers\App::webRequestConfig();
            /** @var craft\web\Request $request */
            $request = Craft::createObject($config);
            $request->csrfCookie = Craft::cookieConfig([], $request);
            return $request;
        },
        'response' => function() {
            $config = craft\helpers\App::webResponseConfig();
            return Craft::createObject($config);
        },
        'session' => function() {
            $config = craft\helpers\App::sessionConfig();
            return Craft::createObject($config);
        },
        'user' => function() {
            $config = craft\helpers\App::userConfig();
            return Craft::createObject($config);
        },
        'errorHandler' => [
            'class' => craft\web\ErrorHandler::class,
            'errorAction' => 'templates/render-error',
        ],
    ],
    'controllerNamespace' => 'craft\\controllers',
];
