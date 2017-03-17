<?php

return [
    'id' => 'CraftCMS',
    'name' => 'Craft CMS',
    'version' => '3.0.0-beta.7',
    'schemaVersion' => '3.0.37',
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
        'tasks' => [
            'class' => craft\services\Tasks::class,
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
        'resources' => [
            'class' => craft\services\Resources::class,
            'dateParam' => 'd',
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
            $configService = Craft::$app->getConfig();

            $config = [
                'class' => craft\web\AssetManager::class,
                'basePath' => $configService->get('resourceBasePath'),
                'baseUrl' => $configService->get('resourceBaseUrl'),
                'fileMode' => $configService->get('defaultFileMode'),
                'dirMode' => $configService->get('defaultDirMode'),
            ];

            return Craft::createObject($config);
        },

        'cache' => function() {
            $configService = Craft::$app->getConfig();
            $cacheMethod = $configService->get('cacheMethod');

            switch ($cacheMethod) {
                case 'apc':
                    $config = [
                        'class' => yii\caching\ApcCache::class,
                        'useApcu' => $configService->get('useApcu', craft\services\Config::CATEGORY_APC),
                    ];
                    break;
                case 'db':
                    $config = [
                        'class' => yii\caching\DbCache::class,
                        'gcProbability' => $configService->get('gcProbability', craft\services\Config::CATEGORY_DBCACHE),
                        'cacheTable' => '{{%'.$configService->get('cacheTableName', craft\services\Config::CATEGORY_DBCACHE).'}}',
                    ];
                    break;
                case 'file':
                    $config = [
                        'class' => \yii\caching\FileCache::class,
                        'cachePath' => $configService->get('cachePath', craft\services\Config::CATEGORY_FILECACHE),
                        'gcProbability' => $configService->get('gcProbability', craft\services\Config::CATEGORY_FILECACHE),
                        'fileMode' => $configService->get('defaultFileMode'),
                        'dirMode' => $configService->get('defaultDirMode'),
                    ];
                    break;
                case 'memcache':
                    $config = [
                        'class' => yii\caching\MemCache::class,
                        'servers' => $configService->get('servers', craft\services\Config::CATEGORY_MEMCACHE),
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
                    throw new yii\base\InvalidConfigException('Unsupported cacheMethod config setting value: '.$cacheMethod);
            }

            $config['defaultDuration'] = $configService->getCacheDuration();

            return Craft::createObject($config);
        },

        'db' => function() {
            // Build the DSN string
            $configService = Craft::$app->getConfig();
            $unixSocket = $configService->get('unixSocket', craft\services\Config::CATEGORY_DB);
            $database = $configService->get('database', craft\services\Config::CATEGORY_DB);
            $driver = $configService->get('driver', craft\services\Config::CATEGORY_DB);
            $dsn = $configService->get('dsn', craft\services\Config::CATEGORY_DB);

            // Make sure it's a supported driver
            if (!in_array($driver, [
                craft\db\Connection::DRIVER_MYSQL,
                craft\db\Connection::DRIVER_PGSQL
            ], true)
            ) {
                throw new yii\db\Exception('Unsupported connection type: '.$driver);
            }

            if (!$dsn) {
                if ($driver === craft\db\Connection::DRIVER_MYSQL && $unixSocket) {
                    $dsn = $driver.':unix_socket='.strtolower($unixSocket).';dbname='.$database.';';
                } else {
                    $server = $configService->get('server', craft\services\Config::CATEGORY_DB);
                    $port = $configService->getDbPort();

                    $dsn = $driver.':host='.strtolower($server).
                        ';dbname='.$database.
                        ';port='.strtolower($port).';';
                }
            }

            $config = [
                'class' => craft\db\Connection::class,
                'dsn' => $dsn,
                'username' => $configService->get('user', craft\services\Config::CATEGORY_DB),
                'password' => $configService->get('password', craft\services\Config::CATEGORY_DB),
                'charset' => $configService->get('charset', craft\services\Config::CATEGORY_DB),
                'tablePrefix' => $configService->getDbTablePrefix(),
                'schemaMap' => [
                    'mysql' => craft\db\mysql\Schema::class,
                    'pgsql' => craft\db\pgsql\Schema::class,
                ],
                'commandClass' => \craft\db\Command::class,
                'attributes' => $configService->get('attributes', craft\services\Config::CATEGORY_DB),
            ];

            $db = Craft::createObject($config);

            // Set the Yii driver name from the config setting.
            /** @var craft\db\Connection $db */
            $db->setDriverName($driver);

            return $db;
        },

        'mailer' => function() {
            $settings = Craft::$app->getSystemSettings()->getEmailSettings();

            return craft\helpers\MailerHelper::createMailer($settings);
        },

        'locale' => function() {
            return Craft::$app->getI18n()->getLocaleById(Craft::$app->language);
        },

        'mutex' => function() {
            $configService = Craft::$app->getConfig();

            $config = [
                'class' => craft\mutex\FileMutex::class,
                'fileMode' => $configService->get('defaultFileMode'),
                'dirMode' => $configService->get('defaultDirMode'),
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

            $configService = Craft::$app->getConfig();

            $target = [
                'class' => craft\log\FileTarget::class,
                'fileMode' => $configService->get('defaultFileMode'),
                'dirMode' => $configService->get('defaultDirMode'),
            ];

            if ($isConsoleRequest) {
                $target['logFile'] = '@storage/logs/console.log';
            } else {
                $target['logFile'] = '@storage/logs/web.log';

                // Only log errors and warnings, unless Craft is running in Dev Mode or it's being installed/updated
                if (!YII_DEBUG && Craft::$app->getIsInstalled() && !Craft::$app->getIsUpdating()) {
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
