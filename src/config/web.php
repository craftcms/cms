<?php

return [
    'components' => [
        'request' => [
            'class' => craft\app\web\Request::class,
            'enableCookieValidation' => true,
        ],
        'response' => craft\app\web\Response::class,
        'session' => craft\app\web\Session::class,
        'urlManager' => [
            'class' => craft\app\web\UrlManager::class,
            'enablePrettyUrl' => true,
            'ruleConfig' => ['class' => craft\app\web\UrlRule::class],
        ],
        'user' => [
            'class' => craft\app\web\User::class,
            'identityClass' => craft\app\elements\User::class,
            'enableAutoLogin' => true,
            'autoRenewCookie' => true,
        ],
        'errorHandler' => [
            'class' => craft\app\web\ErrorHandler::class,
            'errorAction' => 'templates/render-error'
        ]
    ],
    'modules' => [
        'debug' => [
            'class' => yii\debug\Module::class,
            'allowedIPs' => ['*'],
            'panels' => [
                'config' => false,
                'info' => craft\app\debug\InfoPanel::class,
                'request' => yii\debug\panels\RequestPanel::class,
                'log' => yii\debug\panels\LogPanel::class,
                'deprecated' => craft\app\debug\DeprecatedPanel::class,
                'profiling' => yii\debug\panels\ProfilingPanel::class,
                'db' => yii\debug\panels\DbPanel::class,
                'assets' => yii\debug\panels\AssetPanel::class,
                'mail' => yii\debug\panels\MailPanel::class,
            ]
        ]
    ],
];
