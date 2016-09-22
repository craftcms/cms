<?php

use craft\app\db\MigrationManager;
use craft\app\enums\ConfigCategory;
use craft\app\log\FileTarget;
use yii\base\InvalidConfigException;
use yii\log\Logger;

return [
    // Non-configured components
    // -------------------------------------------------------------------------

    'assets' => craft\app\services\Assets::class,
    'assetIndexer' => craft\app\services\AssetIndexer::class,
    'assetTransforms' => craft\app\services\AssetTransforms::class,
    'categories' => craft\app\services\Categories::class,
    'config' => craft\app\services\Config::class,
    'content' => craft\app\services\Content::class,
    'dashboard' => craft\app\services\Dashboard::class,
    'deprecator' => craft\app\services\Deprecator::class,
    'elementIndexes' => craft\app\services\ElementIndexes::class,
    'elements' => craft\app\services\Elements::class,
    'entries' => craft\app\services\Entries::class,
    'entryRevisions' => craft\app\services\EntryRevisions::class,
    'et' => craft\app\services\Et::class,
    'feeds' => craft\app\services\Feeds::class,
    'fields' => craft\app\services\Fields::class,
    'globals' => craft\app\services\Globals::class,
    'images' => craft\app\services\Images::class,
    'matrix' => craft\app\services\Matrix::class,
    'path' => craft\app\services\Path::class,
    'plugins' => craft\app\services\Plugins::class,
    'relations' => craft\app\services\Relations::class,
    'routes' => craft\app\services\Routes::class,
    'search' => craft\app\services\Search::class,
    'sections' => craft\app\services\Sections::class,
    'security' => craft\app\services\Security::class,
    'structures' => craft\app\services\Structures::class,
    'tags' => craft\app\services\Tags::class,
    'tasks' => craft\app\services\Tasks::class,
    'templateCaches' => craft\app\services\TemplateCaches::class,
    'tokens' => craft\app\services\Tokens::class,
    'updates' => craft\app\services\Updates::class,
    'users' => craft\app\services\Users::class,
    'view' => craft\app\web\View::class,
    'volumes' => craft\app\services\Volumes::class,

    // Configured components
    // -------------------------------------------------------------------------

    'contentMigrator' => [
        'class' => MigrationManager::class,
        'type' => MigrationManager::TYPE_CONTENT,
        'migrationNamespace' => 'craft\contentmigrations',
        'migrationPath' => "@contentMigrations",
    ],
    'migrator' => [
        'class' => MigrationManager::class,
        'type' => MigrationManager::TYPE_APP,
        'migrationNamespace' => 'craft\app\migrations',
        'migrationPath' => '@app/migrations',
    ],
    'resources' => [
        'class' => craft\app\services\Resources::class,
        'dateParam' => 'd',
    ],
    'sites' => [
        'class' => craft\app\services\Sites::class,
        'currentSite' => null,
    ],
    'systemSettings' => [
        'class' => craft\app\services\SystemSettings::class,
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
        'class' => craft\app\i18n\I18N::class,
        'translations' => [
            'yii' => [
                'class' => craft\app\i18n\PhpMessageSource::class,
                'sourceLanguage' => 'en-US',
                'basePath' => '@yii/messages',
                'forceTranslation' => true,
                'allowOverrides' => true,
            ],
            'app' => [
                'class' => craft\app\i18n\PhpMessageSource::class,
                'sourceLanguage' => 'en-US',
                'basePath' => '@app/translations',
                'forceTranslation' => true,
                'allowOverrides' => true,
            ],
            'site' => [
                'class' => craft\app\i18n\PhpMessageSource::class,
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
            'class' => craft\app\web\AssetManager::class,
            'basePath' => $configService->get('resourceBasePath'),
            'baseUrl' => $configService->get('resourceBaseUrl')
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
                    'useApcu' => $configService->get('useApcu', ConfigCategory::ApcCache),
                ];
                break;
            case 'db':
                $config =[
                    'class' => yii\caching\DbCache::class,
                    'gcProbability' => $configService->get('gcProbability', ConfigCategory::DbCache),
                    'cacheTable' => '{{%'.$configService->get('cacheTableName', ConfigCategory::DbCache).'}}',
                ];
                break;
            case 'file':
                $config = [
                    'class' => craft\app\cache\FileCache::class,
                    'cachePath' => $configService->get('cachePath', ConfigCategory::FileCache),
                    'gcProbability' => $configService->get('gcProbability', ConfigCategory::FileCache),
                ];
                break;
            case 'memcache':
                $config = [
                    'class' => yii\caching\MemCache::class,
                    'servers' => $configService->get('servers', ConfigCategory::Memcache),
                    'useMemcached' => $configService->get('useMemcached', ConfigCategory::Memcache),
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
        $unixSocket = $configService->get('unixSocket', ConfigCategory::Db);
        $database = $configService->get('database', ConfigCategory::Db);

        if (!empty($unixSocket)) {
            $dsn = 'mysql:unix_socket='.strtolower($unixSocket).
                ';dbname='.$database.';';
        } else {
            $server = $configService->get('server', ConfigCategory::Db);
            $port = $configService->get('port', ConfigCategory::Db);
            $dsn = 'mysql:host='.strtolower($server).
                ';dbname='.$database.
                ';port='.strtolower($port).';';
        }

        $config = [
            'class' => craft\app\db\Connection::class,
            'dsn' => $dsn,
            'emulatePrepare' => true,
            'username' => $configService->get('user', ConfigCategory::Db),
            'password' => $configService->get('password', ConfigCategory::Db),
            'charset' => $configService->get('charset', ConfigCategory::Db),
            'tablePrefix' => $configService->getDbTablePrefix(),
            'schemaMap' => [
                'mysql' => craft\app\db\mysql\Schema::class,
            ],
        ];

        return Craft::createObject($config);
    },

    'mailer' => function() {
        $config = Craft::$app->getSystemSettings()->getSettings('mailer');

        if (!$config) {
            return null;
        }

        return Craft::createObject($config);
    },

    'locale' => function() {
        return Craft::$app->getI18n()->getLocaleById(Craft::$app->language);
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
        $fileTarget = new FileTarget();

        if ($isConsoleRequest) {
            $fileTarget->logFile = Craft::getAlias('@storage/logs/console.log');
        } else {
            $fileTarget->logFile = Craft::getAlias('@storage/logs/web.log');

            // Only log errors and warnings, unless Craft is running in Dev Mode or it's being updated
            if (!$configService->get('devMode') || !Craft::$app->getIsUpdating()) {
                $fileTarget->setLevels(Logger::LEVEL_ERROR | Logger::LEVEL_WARNING);
            }
        }

        $fileTarget->fileMode = $configService->get('defaultFilePermissions');
        $fileTarget->dirMode = $configService->get('defaultFolderPermissions');

        $config = [
            'class' => yii\log\Dispatcher::class,
            'targets' => [
                $fileTarget
            ]
        ];

        return Craft::createObject($config);
    },
];
