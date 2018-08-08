<?php

return [
    'class' => craft\web\Application::class,
    'components' => [
        'assetManager' => function() {
            $config = craft\helpers\App::assetManagerConfig();
            return Craft::createObject($config);
        },
        'request' => function() {
            $config = craft\helpers\App::webRequestConfig();
            /** @var craft\web\Request $request */
            $request = Craft::createObject($config);
            $request->csrfCookie = Craft::cookieConfig([], $request);
            return $request;
        },
        'response' => [
            'class' => craft\web\Response::class,
        ],
        'session' => function() {
            $config = craft\helpers\App::sessionConfig();
            return Craft::createObject($config);
        },
        'urlManager' => [
            'class' => craft\web\UrlManager::class,
            'enablePrettyUrl' => true,
            'ruleConfig' => ['class' => craft\web\UrlRule::class],
        ],
        'user' => function() {
            $config = craft\helpers\App::userConfig();
            return Craft::createObject($config);
        },
        'errorHandler' => [
            'class' => craft\web\ErrorHandler::class,
            'errorAction' => 'templates/render-error'
        ]
    ],
    'controllerNamespace' => 'craft\\controllers',
];
