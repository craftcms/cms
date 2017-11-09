<?php

return [
    'class' => \craft\web\Application::class,
    'components' => [
        'request' => function() {
            $generalConfig = Craft::$app->getConfig()->getGeneral();

            /** @var craft\web\Request $request */
            $request = Craft::createObject([
                'class' => craft\web\Request::class,
                'enableCookieValidation' => true,
                'cookieValidationKey' => $generalConfig->securityKey,
                'enableCsrfValidation' => $generalConfig->enableCsrfProtection,
                'enableCsrfCookie' => $generalConfig->enableCsrfCookie,
                'csrfParam' => $generalConfig->csrfTokenName,
            ]);

            $request->csrfCookie = Craft::cookieConfig([], $request);
            return $request;
        },
        'response' => [
            'class' => craft\web\Response::class,
        ],
        'session' => function() {
            $stateKeyPrefix = md5('Craft.'.craft\web\Session::class.'.'.Craft::$app->id);

            return Craft::createObject([
                'class' => craft\web\Session::class,
                'flashParam' => $stateKeyPrefix.'__flash',
                'authAccessParam' => $stateKeyPrefix.'__auth_access',
                'name' => Craft::$app->getConfig()->getGeneral()->phpSessionName,
                'cookieParams' => Craft::cookieConfig(),
            ]);
        },
        'urlManager' => [
            'class' => craft\web\UrlManager::class,
            'enablePrettyUrl' => true,
            'ruleConfig' => ['class' => craft\web\UrlRule::class],
        ],
        'user' => function() {
            $configService = Craft::$app->getConfig();
            $generalConfig = $configService->getGeneral();
            $request = Craft::$app->getRequest();

            if ($request->getIsConsoleRequest() || $request->getIsSiteRequest()) {
                $loginUrl = craft\helpers\UrlHelper::siteUrl($generalConfig->getLoginPath());
            } else {
                $loginUrl = craft\helpers\UrlHelper::cpUrl('login');
            }

            $stateKeyPrefix = md5('Craft.'.craft\web\User::class.'.'.Craft::$app->id);

            return Craft::createObject([
                'class' => craft\web\User::class,
                'identityClass' => craft\elements\User::class,
                'enableAutoLogin' => true,
                'autoRenewCookie' => true,
                'loginUrl' => $loginUrl,
                'authTimeout' => $generalConfig->userSessionDuration ?: null,
                'identityCookie' => Craft::cookieConfig(['name' => $stateKeyPrefix.'_identity']),
                'usernameCookie' => Craft::cookieConfig(['name' => $stateKeyPrefix.'_username']),
                'idParam' => $stateKeyPrefix.'__id',
                'authTimeoutParam' => $stateKeyPrefix.'__expire',
                'absoluteAuthTimeoutParam' => $stateKeyPrefix.'__absoluteExpire',
                'returnUrlParam' => $stateKeyPrefix.'__returnUrl',
            ]);
        },
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
                'user' => craft\debug\UserPanel::class,
                'router' => [
                    'class' => yii\debug\panels\RouterPanel::class,
                    'categories' => [
                        'craft\web\UrlManager::_getMatchedElementRoute',
                        'craft\web\UrlManager::_getMatchedUrlRoute',
                        'craft\web\UrlManager::_getTemplateRoute',
                        'craft\web\UrlManager::_getTokenRoute',
                    ]
                ],
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
