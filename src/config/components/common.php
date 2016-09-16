<?php

use craft\app\cache\FileCache;
use craft\app\db\Connection;
use craft\app\db\MigrationManager;
use craft\app\db\mysql\Schema as MysqlSchema;
use craft\app\enums\ConfigCategory;
use craft\app\log\FileTarget;
use craft\app\web\AssetManager;
use yii\base\InvalidConfigException;
use yii\caching\ApcCache;
use yii\caching\DbCache;
use yii\caching\MemCache;
use yii\caching\WinCache;
use yii\caching\XCache;
use yii\caching\ZendDataCache;
use yii\log\Dispatcher;
use yii\log\Logger;

return [
    // Non-configured components
    // -------------------------------------------------------------------------

    'assets' => 'craft\app\services\Assets',
    'assetIndexer' => 'craft\app\services\AssetIndexer',
    'assetTransforms' => 'craft\app\services\AssetTransforms',
    'categories' => 'craft\app\services\Categories',
    'config' => 'craft\app\services\Config',
    'content' => 'craft\app\services\Content',
    'dashboard' => 'craft\app\services\Dashboard',
    'deprecator' => 'craft\app\services\Deprecator',
    'elementIndexes' => 'craft\app\services\ElementIndexes',
    'elements' => 'craft\app\services\Elements',
    'email' => 'craft\app\services\Email',
    'entries' => 'craft\app\services\Entries',
    'entryRevisions' => 'craft\app\services\EntryRevisions',
    'et' => 'craft\app\services\Et',
    'feeds' => 'craft\app\services\Feeds',
    'fields' => 'craft\app\services\Fields',
    'globals' => 'craft\app\services\Globals',
    'install' => 'craft\app\services\Install',
    'images' => 'craft\app\services\Images',
    'matrix' => 'craft\app\services\Matrix',
    'path' => 'craft\app\services\Path',
    'plugins' => 'craft\app\services\Plugins',
    'relations' => 'craft\app\services\Relations',
    'routes' => 'craft\app\services\Routes',
    'search' => 'craft\app\services\Search',
    'sections' => 'craft\app\services\Sections',
    'security' => 'craft\app\services\Security',
    'structures' => 'craft\app\services\Structures',
    'tags' => 'craft\app\services\Tags',
    'tasks' => 'craft\app\services\Tasks',
    'templateCaches' => 'craft\app\services\TemplateCaches',
    'tokens' => 'craft\app\services\Tokens',
    'updates' => 'craft\app\services\Updates',
    'users' => 'craft\app\services\Users',
    'view' => 'craft\app\web\View',
    'volumes' => 'craft\app\services\Volumes',

    // Configured components
    // -------------------------------------------------------------------------

    'contentMigrator' => [
        'class' => MigrationManager::className(),
        'type' => MigrationManager::TYPE_CONTENT,
        'migrationNamespace' => "craft\\contentmigrations",
        'migrationPath' => "@contentMigrations",
    ],
    'migrator' => [
        'class' => MigrationManager::className(),
        'type' => MigrationManager::TYPE_APP,
        'migrationNamespace' => 'craft\app\migrations',
        'migrationPath' => '@app/migrations',
    ],
    'resources' => [
        'class' => 'craft\app\services\Resources',
        'dateParam' => 'd',
    ],
    'sites' => [
        'class' => 'craft\app\services\Sites',
        'currentSite' => null,
    ],
    'systemSettings' => [
        'class' => 'craft\app\services\SystemSettings',
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
        'class' => 'craft\app\i18n\I18N',
        'translations' => [
            'yii' => [
                'class' => 'craft\app\i18n\PhpMessageSource',
                'sourceLanguage' => 'en-US',
                'basePath' => '@yii/messages',
                'forceTranslation' => true,
                'allowOverrides' => true,
            ],
            'app' => [
                'class' => 'craft\app\i18n\PhpMessageSource',
                'sourceLanguage' => 'en-US',
                'basePath' => '@app/translations',
                'forceTranslation' => true,
                'allowOverrides' => true,
            ],
            'site' => [
                'class' => 'craft\app\i18n\PhpMessageSource',
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
            'class' => AssetManager::className(),
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
                    'class' => ApcCache::className(),
                    'useApcu' => $configService->get('useApcu', ConfigCategory::ApcCache),
                ];
                break;
            case 'db':
                $config =[
                    'class' => DbCache::className(),
                    'gcProbability' => $configService->get('gcProbability', ConfigCategory::DbCache),
                    'cacheTable' => '{{%'.$configService->get('cacheTableName', ConfigCategory::DbCache).'}}',
                ];
                break;
            case 'file':
                $config = [
                    'class' => FileCache::className(),
                    'cachePath' => $configService->get('cachePath', ConfigCategory::FileCache),
                    'gcProbability' => $configService->get('gcProbability', ConfigCategory::FileCache),
                ];
                break;
            case 'memcache':
                $config = [
                    'class' => MemCache::className(),
                    'servers' => $configService->get('servers', ConfigCategory::Memcache),
                    'useMemcached' => $configService->get('useMemcached', ConfigCategory::Memcache),
                ];
                break;
            case 'wincache':
                $config = WinCache::className();
                break;
            case 'xcache':
                $config = XCache::className();
                break;
            case 'zenddata':
                $config = ZendDataCache::className();
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
            'class' => Connection::className(),
            'dsn' => $dsn,
            'emulatePrepare' => true,
            'username' => $configService->get('user', ConfigCategory::Db),
            'password' => $configService->get('password', ConfigCategory::Db),
            'charset' => $configService->get('charset', ConfigCategory::Db),
            'tablePrefix' => $configService->getDbTablePrefix(),
            'schemaMap' => [
                'mysql' => MysqlSchema::className(),
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
            'class' => Dispatcher::className(),
            'targets' => [
                $fileTarget
            ]
        ];

        return Craft::createObject($config);
    },
];
