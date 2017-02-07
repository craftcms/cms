<?php

use craft\db\Connection;
use craft\db\MigrationManager;
use craft\helpers\MailerHelper;
use craft\log\FileTarget;
use craft\services\Config;
use yii\base\InvalidConfigException;
use yii\db\Exception as DbException;
use yii\log\Logger;

return [
    // Non-configured components
    // -------------------------------------------------------------------------

    'assets' => craft\services\Assets::class,
    'assetIndexer' => craft\services\AssetIndexer::class,
    'assetTransforms' => craft\services\AssetTransforms::class,
    'categories' => craft\services\Categories::class,
    'content' => craft\services\Content::class,
    'dashboard' => craft\services\Dashboard::class,
    'deprecator' => craft\services\Deprecator::class,
    'elementIndexes' => craft\services\ElementIndexes::class,
    'elements' => craft\services\Elements::class,
    'entries' => craft\services\Entries::class,
    'entryRevisions' => craft\services\EntryRevisions::class,
    'et' => craft\services\Et::class,
    'feeds' => craft\feeds\Feeds::class,
    'fields' => craft\services\Fields::class,
    'globals' => craft\services\Globals::class,
    'images' => craft\services\Images::class,
    'matrix' => craft\services\Matrix::class,
    'path' => craft\services\Path::class,
    'plugins' => craft\services\Plugins::class,
    'relations' => craft\services\Relations::class,
    'routes' => craft\services\Routes::class,
    'search' => craft\services\Search::class,
    'sections' => craft\services\Sections::class,
    'security' => craft\services\Security::class,
    'structures' => craft\services\Structures::class,
    'tags' => craft\services\Tags::class,
    'tasks' => craft\services\Tasks::class,
    'templateCaches' => craft\services\TemplateCaches::class,
    'tokens' => craft\services\Tokens::class,
    'updates' => craft\services\Updates::class,
    'users' => craft\services\Users::class,
    'view' => craft\web\View::class,
    'volumes' => craft\services\Volumes::class,
    'utilities' => craft\services\Utilities::class,

    // Configured components
    // -------------------------------------------------------------------------

    'contentMigrator' => [
        'class' => MigrationManager::class,
        'type' => MigrationManager::TYPE_CONTENT,
        'migrationNamespace' => 'craft\contentmigrations',
        'migrationPath' => '@contentMigrations',
    ],
    'migrator' => [
        'class' => MigrationManager::class,
        'type' => MigrationManager::TYPE_APP,
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
                    'useApcu' => $configService->get('useApcu', Config::CATEGORY_APC),
                ];
                break;
            case 'db':
                $config = [
                    'class' => yii\caching\DbCache::class,
                    'gcProbability' => $configService->get('gcProbability', Config::CATEGORY_DBCACHE),
                    'cacheTable' => '{{%'.$configService->get('cacheTableName', Config::CATEGORY_DBCACHE).'}}',
                ];
                break;
            case 'file':
                $config = [
                    'class' => \yii\caching\FileCache::class,
                    'cachePath' => $configService->get('cachePath', Config::CATEGORY_FILECACHE),
                    'gcProbability' => $configService->get('gcProbability', Config::CATEGORY_FILECACHE),
                    'fileMode' => $configService->get('defaultFileMode'),
                    'dirMode' => $configService->get('defaultDirMode'),
                ];
                break;
            case 'memcache':
                $config = [
                    'class' => yii\caching\MemCache::class,
                    'servers' => $configService->get('servers', Config::CATEGORY_MEMCACHE),
                    'useMemcached' => true,
                ];
                break;
            case 'wincache':
                $config = yii\caching\WinCache::class;
                break;
            case 'xcache':
                $config = yii\caching\XCache::class;
                break;
            case 'zenddata':
                $config = yii\caching\ZendDataCache::class;
                break;
            default:
                throw new InvalidConfigException('Unsupported cacheMethod config setting value: '.$cacheMethod);
        }

        return Craft::createObject($config);
    },

    'db' => function() {
        // Build the DSN string
        $configService = Craft::$app->getConfig();
        $unixSocket = $configService->get('unixSocket', Config::CATEGORY_DB);
        $database = $configService->get('database', Config::CATEGORY_DB);
        $driver = $configService->get('driver', Config::CATEGORY_DB);
        $dsn = $configService->get('dsn', Config::CATEGORY_DB);

        // Make sure it's a supported driver
        if (!in_array($driver, [
            Connection::DRIVER_MYSQL,
            Connection::DRIVER_PGSQL
        ], true)
        ) {
            throw new DbException('Unsupported connection type: '.$driver);
        }

        if ($dsn === '') {
            if ($driver === Connection::DRIVER_MYSQL && !empty($unixSocket)) {
                $dsn = $driver.':unix_socket='.strtolower($unixSocket).';dbname='.$database.';';
            } else {
                $server = $configService->get('server', Config::CATEGORY_DB);
                $port = $configService->getDbPort();

                $dsn = $driver.':host='.strtolower($server).
                    ';dbname='.$database.
                    ';port='.strtolower($port).';';
            }
        }

        $config = [
            'class' => craft\db\Connection::class,
            'dsn' => $dsn,
            'username' => $configService->get('user', Config::CATEGORY_DB),
            'password' => $configService->get('password', Config::CATEGORY_DB),
            'charset' => $configService->get('charset', Config::CATEGORY_DB),
            'tablePrefix' => $configService->getDbTablePrefix(),
            'schemaMap' => [
                'mysql' => craft\db\mysql\Schema::class,
                'pgsql' => craft\db\pgsql\Schema::class,
            ],
            'commandClass' => \craft\db\Command::class,
            'attributes' => $configService->get('attributes', Config::CATEGORY_DB),
        ];

        $db = Craft::createObject($config);

        // Set the Yii driver name from the config setting.
        /** @var Connection $db */
        $db->setDriverName($driver);

        return $db;
    },

    'mailer' => function() {
        $settings = Craft::$app->getSystemSettings()->getEmailSettings();

        return MailerHelper::createMailer($settings);
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
            'class' => FileTarget::class,
            'fileMode' => $configService->get('defaultFileMode'),
            'dirMode' => $configService->get('defaultDirMode'),
        ];

        if ($isConsoleRequest) {
            $target['logFile'] = '@storage/logs/console.log';
        } else {
            $target['logFile'] = '@storage/logs/web.log';

            // Only log errors and warnings, unless Craft is running in Dev Mode or it's being installed/updated
            if (!$configService->get('devMode') && Craft::$app->getIsInstalled() && !Craft::$app->getIsUpdating()) {
                $target['levels'] = Logger::LEVEL_ERROR | Logger::LEVEL_WARNING;
            }
        }

        return Craft::createObject([
            'class' => yii\log\Dispatcher::class,
            'targets' => [
                $target,
            ]
        ]);
    },
];
