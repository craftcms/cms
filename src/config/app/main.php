<?php

return [
    'id' => 'CraftCMS',
    'name' => 'Craft CMS',
    'version' => '3.0.0-beta.30',
    'schemaVersion' => '3.0.61',
    'minVersionRequired' => '2.6.2788',
    'basePath' => dirname(__DIR__, 2), // Defines the @app alias
    'runtimePath' => '@storage/runtime', // Defines the @runtime alias
    'controllerNamespace' => 'craft\controllers',

    'components' => [
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
        'et' => [
            'class' => craft\services\Et::class,
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
                    'photoVolumeId' => null
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

        'assetManager' => function() {
            $generalConfig = Craft::$app->getConfig()->getGeneral();

            $config = [
                'class' => craft\web\AssetManager::class,
                'basePath' => $generalConfig->resourceBasePath,
                'baseUrl' => $generalConfig->resourceBaseUrl,
                'fileMode' => $generalConfig->defaultFileMode,
                'dirMode' => $generalConfig->defaultDirMode,
            ];

            return Craft::createObject($config);
        },

        'cache' => function() {
            $configService = Craft::$app->getConfig();
            $generalConfig = $configService->getGeneral();

            switch ($generalConfig->cacheMethod) {
                case 'apc':
                    $config = [
                        'class' => yii\caching\ApcCache::class,
                        'useApcu' => true,
                    ];
                    break;
                case 'db':
                    $dbCacheConfig = $configService->getDbCache();
                    $config = [
                        'class' => yii\caching\DbCache::class,
                        'gcProbability' => $dbCacheConfig->gcProbability,
                        'cacheTable' => '{{%'.$dbCacheConfig->cacheTableName.'}}',
                    ];
                    break;
                case 'file':
                    $fileCacheConfig = $configService->getFileCache();
                    $config = [
                        'class' => \yii\caching\FileCache::class,
                        'cachePath' => $fileCacheConfig->cachePath,
                        'gcProbability' => $fileCacheConfig->gcProbability,
                        'fileMode' => $generalConfig->defaultFileMode,
                        'dirMode' => $generalConfig->defaultDirMode,
                    ];
                    break;
                case 'memcache':
                    $config = [
                        'class' => yii\caching\MemCache::class,
                        'servers' => $configService->getMemCache()->servers,
                        'useMemcached' => true,
                    ];
                    break;
                case 'wincache':
                    $config = [
                        'class' => yii\caching\WinCache::class,
                    ];
                    break;
                case 'xcache':
                    $config = [
                        'class' => yii\caching\XCache::class,
                    ];
                    break;
                case 'zenddata':
                    $config = [
                        'class' => yii\caching\ZendDataCache::class,
                    ];
                    break;
                default:
                    throw new yii\base\InvalidConfigException('Unsupported cacheMethod config setting value: '.$generalConfig->cacheMethod);
            }

            $config['defaultDuration'] = $generalConfig->cacheDuration;

            return Craft::createObject($config);
        },

        'db' => function() {
            $dbConfig = Craft::$app->getConfig()->getDb();

            if ($dbConfig->driver === \craft\config\DbConfig::DRIVER_MYSQL) {
                $schemaClass = craft\db\mysql\Schema::class;
            } else {
                $schemaClass = craft\db\pgsql\Schema::class;
            }

            return Craft::createObject([
                'class' => craft\db\Connection::class,
                'driverName' => $dbConfig->driver,
                'dsn' => $dbConfig->dsn,
                'username' => $dbConfig->user,
                'password' => $dbConfig->password,
                'charset' => $dbConfig->charset,
                'tablePrefix' => $dbConfig->tablePrefix,
                'schemaMap' => [
                    $dbConfig->driver => [
                        'class' => $schemaClass,
                    ]
                ],
                'commandClass' => \craft\db\Command::class,
                'attributes' => $dbConfig->attributes,
            ]);
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
