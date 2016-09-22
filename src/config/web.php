<?php

return [
    'components' => [
        'request' => [
            'class' => \craft\app\web\Request::class,
            'enableCookieValidation' => true,
        ],
        'response' => \craft\app\web\Response::class,
        'session' => \craft\app\web\Session::class,
        'urlManager' => [
            'class' => \craft\app\web\UrlManager::class,
            'enablePrettyUrl' => true,
            'ruleConfig' => ['class' => \craft\app\web\UrlRule::class],
        ],
        'user' => [
            'class' => \craft\app\web\User::class,
            'identityClass' => \craft\app\elements\User::class,
            'enableAutoLogin' => true,
            'autoRenewCookie' => true,
        ],
        'errorHandler' => [
            'class' => \craft\app\web\ErrorHandler::class,
            'errorAction' => 'templates/render-error'
        ]
    ],
    'modules' => [
        'debug' => [
            'class' => \yii\debug\Module::class,
            'allowedIPs' => ['*'],
            'panels' => [
                'config' => false,
                'info' => ['class' => \craft\app\debug\InfoPanel::class],
                'request' => ['class' => \yii\debug\panels\RequestPanel::class],
                'log' => ['class' => \yii\debug\panels\LogPanel::class],
                'deprecated' => ['class' => \craft\app\debug\DeprecatedPanel::class],
                'profiling' => ['class' => \yii\debug\panels\ProfilingPanel::class],
                'db' => ['class' => \yii\debug\panels\DbPanel::class],
                'assets' => ['class' => \yii\debug\panels\AssetPanel::class],
                'mail' => ['class' => \yii\debug\panels\MailPanel::class],
            ]
        ]
    ],
];
