<?php

return [
    'id' => 'CraftCMS',
    'name' => 'Craft CMS',
    'version' => '3.0.7',
    'schemaVersion' => '3.0.91',
    'minVersionRequired' => '2.6.2788',
    'basePath' => dirname(__DIR__), // Defines the @app alias
    'runtimePath' => '@storage/runtime', // Defines the @runtime alias
    'controllerNamespace' => 'craft\controllers',

    'components' => [
        'api' => [
            'class' => craft\services\Api::class,
        ],
        'assets' => [
            'class' => craft\services\Assets::class,
        ],
        'assetIndexer' => [
            'class' => craft\services\AssetIndexer::class,
        ],
        'assetTransforms' => [
            'class' => craft\services\AssetTransforms::class,
        ],
        'categories' => [
            'class' => craft\services\Categories::class,
        ],
        'composer' => [
            'class' => \craft\services\Composer::class,
        ],
        'content' => [
            'class' => craft\services\Content::class,
        ],
        'dashboard' => [
            'class' => craft\services\Dashboard::class,
        ],
        'deprecator' => [
            'class' => craft\services\Deprecator::class,
        ],
        'elementIndexes' => [
            'class' => craft\services\ElementIndexes::class,
        ],
        'elements' => [
            'class' => craft\services\Elements::class,
        ],
        'entries' => [
            'class' => craft\services\Entries::class,
        ],
        'entryRevisions' => [
            'class' => craft\services\EntryRevisions::class,
        ],
        'feeds' => [
            'class' => craft\feeds\Feeds::class,
        ],
        'fields' => [
            'class' => craft\services\Fields::class,
        ],
        'globals' => [
            'class' => craft\services\Globals::class,
        ],
        'images' => [
            'class' => craft\services\Images::class,
        ],
        'matrix' => [
            'class' => craft\services\Matrix::class,
        ],
        'path' => [
            'class' => craft\services\Path::class,
        ],
        'plugins' => [
            'class' => craft\services\Plugins::class,
        ],
        'pluginStore' => [
            'class' => craft\services\PluginStore::class,
        ],
        'queue' => [
            'class' => craft\queue\Queue::class,
        ],
        'relations' => [
            'class' => craft\services\Relations::class,
        ],
        'routes' => [
            'class' => craft\services\Routes::class,
        ],
        'search' => [
            'class' => craft\services\Search::class,
        ],
        'sections' => [
            'class' => craft\services\Sections::class,
        ],
        'security' => [
            'class' => craft\services\Security::class,
        ],
        'structures' => [
            'class' => craft\services\Structures::class,
        ],
        'systemMessages' => [
            'class' => craft\services\SystemMessages::class,
        ],
        'tags' => [
            'class' => craft\services\Tags::class,
        ],
        'templateCaches' => [
            'class' => craft\services\TemplateCaches::class,
        ],
        'tokens' => [
            'class' => craft\services\Tokens::class,
        ],
        'updates' => [
            'class' => craft\services\Updates::class,
        ],
        'users' => [
            'class' => craft\services\Users::class,
        ],
        'view' => [
            'class' => craft\web\View::class,
        ],
        'volumes' => [
            'class' => craft\services\Volumes::class,
        ],
        'userGroups' => [
            'class' => craft\services\UserGroups::class,
        ],
        'userPermissions' => [
            'class' => craft\services\UserPermissions::class,
        ],
        'utilities' => [
            'class' => craft\services\Utilities::class,
        ],
        'contentMigrator' => [
            'class' => craft\db\MigrationManager::class,
            'type' => craft\db\MigrationManager::TYPE_CONTENT,
            'migrationNamespace' => 'craft\contentmigrations',
            'migrationPath' => '@contentMigrations',
        ],
        'migrator' => [
            'class' => craft\db\MigrationManager::class,
            'type' => craft\db\MigrationManager::TYPE_APP,
            'migrationNamespace' => 'craft\migrations',
            'migrationPath' => '@app/migrations',
        ],
        'sites' => [
            'class' => craft\services\Sites::class,
            'currentSite' => null,
        ],
        'systemSettings' => [
            'class' => craft\services\SystemSettings::class,
            'defaults' => [
                'users' => [
                    'requireEmailVerification' => true,
                    'allowPublicRegistration' => false,
                    'defaultGroup' => null,
                    'photoVolumeId' => null,
                    'photoSubpath' => ''
                ],
            ]
        ],
        'i18n' => [
            'class' => craft\i18n\I18N::class,
            'translations' => [
                'yii' => [
                    'class' => craft\i18n\PhpMessageSource::class,
                    'sourceLanguage' => 'en-US',
                    'basePath' => '@yii/messages',
                    'forceTranslation' => true,
                    'allowOverrides' => true,
                ],
                'app' => [
                    'class' => craft\i18n\PhpMessageSource::class,
                    'sourceLanguage' => 'en-US',
                    'basePath' => '@app/translations',
                    'forceTranslation' => true,
                    'allowOverrides' => true,
                ],
                'site' => [
                    'class' => craft\i18n\PhpMessageSource::class,
                    'sourceLanguage' => 'en-US',
                    'basePath' => '@translations',
                    'forceTranslation' => true,
                ],
            ],
        ],

        // Dynamically configured components
        // -------------------------------------------------------------------------

        'cache' => function() {
            $generalConfig = Craft::$app->getConfig()->getGeneral();

            $config = [
                'class' => \yii\caching\FileCache::class,
                'cachePath' => Craft::$app->getPath()->getCachePath(),
                'fileMode' => $generalConfig->defaultFileMode,
                'dirMode' => $generalConfig->defaultDirMode,
                'defaultDuration' => $generalConfig->cacheDuration,
            ];

            return Craft::createObject($config);
        },

        'db' => function() {
            $dbConfig = Craft::$app->getConfig()->getDb();
            return craft\db\Connection::createFromConfig($dbConfig);
        },

        'mailer' => function() {
            $settings = Craft::$app->getSystemSettings()->getEmailSettings();

            return craft\helpers\MailerHelper::createMailer($settings);
        },

        'locale' => function() {
            return Craft::$app->getI18n()->getLocaleById(Craft::$app->language);
        },

        'mutex' => function() {
            $generalConfig = Craft::$app->getConfig()->getGeneral();

            $config = [
                'class' => craft\mutex\FileMutex::class,
                'fileMode' => $generalConfig->defaultFileMode,
                'dirMode' => $generalConfig->defaultDirMode,
            ];

            return Craft::createObject($config);
        },

        'formatter' => function() {
            return Craft::$app->getLocale()->getFormatter();
        },

        'log' => function() {
            // Only log console requests and web requests that aren't getAuthTimeout requests
            $isConsoleRequest = Craft::$app->getRequest()->getIsConsoleRequest();
            if (!$isConsoleRequest && !Craft::$app->getUser()->enableSession) {
                return null;
            }

            $generalConfig = Craft::$app->getConfig()->getGeneral();

            $target = [
                'class' => craft\log\FileTarget::class,
                'fileMode' => $generalConfig->defaultFileMode,
                'dirMode' => $generalConfig->defaultDirMode,
            ];

            if ($isConsoleRequest) {
                $target['logFile'] = '@storage/logs/console.log';
            } else {
                $target['logFile'] = '@storage/logs/web.log';

                // Only log errors and warnings, unless Craft is running in Dev Mode or it's being installed/updated
                if (!YII_DEBUG && Craft::$app->getIsInstalled() && !Craft::$app->getUpdates()->getIsCraftDbMigrationNeeded()) {
                    $target['levels'] = yii\log\Logger::LEVEL_ERROR | yii\log\Logger::LEVEL_WARNING;
                }
            }

            return Craft::createObject([
                'class' => yii\log\Dispatcher::class,
                'targets' => [
                    $target,
                ]
            ]);
        },
    ],
];
