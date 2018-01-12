<?php

return [
    'class' => \craft\web\Application::class,
    'components' => [
        'assetManager' => function() {
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $config = [
                'class' => craft\web\AssetManager::class,
                'basePath' => $generalConfig->resourceBasePath,
                'baseUrl' => $generalConfig->resourceBaseUrl,
                'fileMode' => $generalConfig->defaultFileMode,
                'dirMode' => $generalConfig->defaultDirMode,
                'appendTimestamp' => true,
            ];
            return Craft::createObject($config);
        },
        'request' => function() {
            $generalConfig = Craft::$app->getConfig()->getGeneral();
            $config = [
                'class' => craft\web\Request::class,
                'enableCookieValidation' => true,
                'cookieValidationKey' => $generalConfig->securityKey,
                'enableCsrfValidation' => $generalConfig->enableCsrfProtection,
                'enableCsrfCookie' => $generalConfig->enableCsrfCookie,
                'csrfParam' => $generalConfig->csrfTokenName,
            ];
            if ($generalConfig->trustedHosts !== null) {
                $config['trustedHosts'] = $generalConfig->trustedHosts;
            }
            if ($generalConfig->secureHeaders !== null) {
                $config['secureHeaders'] = $generalConfig->secureHeaders;
            }
            if ($generalConfig->ipHeaders !== null) {
                $config['ipHeaders'] = $generalConfig->ipHeaders;
            }
            if ($generalConfig->secureProtocolHeaders !== null) {
                $config['secureProtocolHeaders'] = $generalConfig->secureProtocolHeaders;
            }
            /** @var craft\web\Request $request */
            $request = Craft::createObject($config);
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
];
