<?php

return [
    'components' => [
        'request' => [
            'class' => craft\web\Request::class,
            'enableCookieValidation' => true,
        ],
        'response' => craft\web\Response::class,
        'session' => craft\web\Session::class,
        'urlManager' => [
            'class' => craft\web\UrlManager::class,
            'enablePrettyUrl' => true,
            'ruleConfig' => ['class' => craft\web\UrlRule::class],
        ],
        'user' => [
            'class' => craft\web\User::class,
            'identityClass' => craft\elements\User::class,
            'enableAutoLogin' => true,
            'autoRenewCookie' => true,
        ],
        'errorHandler' => [
            'class' => craft\web\ErrorHandler::class,
            'errorAction' => 'templates/render-error'
        ]
    ],
    'controllerNamespace' => 'craft\\controllers',
    'modules' => [
        'debug' => [
            'class' => yii\debug\Module::class,
            'allowedIPs' => ['*'],
            'panels' => [
                'config' => false,
                'request' => yii\debug\panels\RequestPanel::class,
                'log' => yii\debug\panels\LogPanel::class,
                'deprecated' => craft\debug\DeprecatedPanel::class,
                'profiling' => yii\debug\panels\ProfilingPanel::class,
                'db' => yii\debug\panels\DbPanel::class,
                'assets' => yii\debug\panels\AssetPanel::class,
                'mail' => yii\debug\panels\MailPanel::class,
            ]
        ]
    ],
];
